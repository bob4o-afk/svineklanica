<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Shared\Enums\CorruptionCategory;
use App\Shared\Enums\FlagSeverity;
use App\Shared\Enums\Sphere;
use App\Support\PublicId\PublicIdGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Modules\Detection\Enums\FlagType;
use Modules\Detection\Models\Flag;
use Modules\Presentation\Support\SectorResolver;
use Modules\Procurement\Enums\TenderStatus;
use Modules\Procurement\Models\Company;
use Modules\Procurement\Models\ContractingAuthority;
use Modules\Procurement\Models\PriceSnapshot;
use Modules\Procurement\Models\Tender;
use Modules\Procurement\Models\TenderItem;

/**
 * A coherent, REAL-shaped demo dataset (CLAUDE.md §5 — a working demo on real data).
 * Everything links up so all seven citizen endpoints return live records: the feed,
 * entity profiles, the price-creep graph, the serial-winner network, the region map,
 * and search. Bulgarian institution/company names are realistic-but-invented; every
 * flag carries a source_url. Idempotent — re-running rebuilds the demo cleanly.
 *
 * NOTE: only run in non-production (wired through DatabaseSeeder).
 */
final class DemoSeeder extends Seeder
{
    private const SERIES_KEY = 'laptops';

    /** @var array<string, ContractingAuthority> */
    private array $authorities = [];

    /** @var array<string, Company> */
    private array $companies = [];

    public function run(): void
    {
        // Authorities + companies first (idempotent upsert) so wipeDemoFlags can scope the
        // wipe to flags whose subject is demo-owned — never touching real detector output.
        $this->seedAuthorities();
        $this->seedCompanies();
        $this->wipeDemoFlags();
        $this->seedLaptopOverpricing();
        $this->seedSerialWinner();
        $this->seedBreadthFlags();
    }

    /**
     * Delete ONLY the flags this seeder owns — those attached to demo tenders (source='demo')
     * or to the demo authorities/companies — so re-seeding rebuilds the demo cleanly without
     * wiping flags produced by real detectors on real ingested data.
     */
    private function wipeDemoFlags(): void
    {
        $tenderIds = Tender::query()->where('source', 'demo')->pluck('id')->all();
        $authorityIds = array_map(static fn (ContractingAuthority $a): int => $a->id, $this->authorities);
        $companyIds = array_map(static fn (Company $c): int => $c->id, $this->companies);

        if ($tenderIds === [] && $authorityIds === [] && $companyIds === []) {
            return;
        }

        Flag::query()
            ->where(function (Builder $q) use ($tenderIds, $authorityIds, $companyIds): void {
                if ($tenderIds !== []) {
                    $q->orWhere(fn (Builder $t) => $t->where('subject_type', 'tender')->whereIn('subject_id', $tenderIds));
                }
                if ($authorityIds !== []) {
                    $q->orWhere(fn (Builder $a) => $a->where('subject_type', 'authority')->whereIn('subject_id', $authorityIds));
                }
                if ($companyIds !== []) {
                    $q->orWhere(fn (Builder $c) => $c->where('subject_type', 'company')->whereIn('subject_id', $companyIds));
                }
            })
            ->delete();
    }

    private function seedAuthorities(): void
    {
        // [key => [name, eik, NUTS3 region code]]
        $rows = [
            'burgas' => ['Община Бургас', '000056814', 'BG341'],
            'plovdiv' => ['Община Пловдив', '000471504', 'BG421'],
            'varna' => ['Община Варна', '000093442', 'BG331'],
            'mvr' => ['Министерство на вътрешните работи', '000695235', 'BG411'],
            'umbal' => ['УМБАЛ „Света Анна" — София', '831605813', 'BG411'],
            'vss' => ['Висш съдебен съвет', '121513231', 'BG411'],
            'belitsa' => ['Община Белица', '000024773', 'BG413'],
            'sofia' => ['Столична община', '000696327', 'BG411'],
        ];

        foreach ($rows as $key => [$name, $eik, $region]) {
            $this->authorities[$key] = ContractingAuthority::updateOrCreate(
                ['eik' => $eik],
                [
                    'public_id' => PublicIdGenerator::generate(),
                    'name' => $name,
                    'region' => $region,
                    'source_url' => 'https://app.eop.bg/buyer/'.$eik,
                ],
            );
        }
    }

    private function seedCompanies(): void
    {
        $rows = [
            'tehnotrade' => ['Техно Трейд ЕООД', '201234567', 'гр. София, ул. Витоша 1', 'Иван Петров', '+359888111222'],
            'stroyinvest' => ['Стройинвест Корект ЕООД', '200111222', 'гр. Бургас, ул. Александровска 10', 'Георги Димитров', '+359888333444'],
            'buildpro' => ['Билд Про Груп ЕООД', '203987654', 'гр. Бургас, ул. Александровска 10', 'Георги Димитров', '+359888333445'],
            'medtech' => ['Медтех Фарма ООД', '202456789', 'гр. София, бул. България 5', 'Мария Колева', '+359888555666'],
            'itsol' => ['АйТи Солюшънс БГ ЕООД', '204555111', 'гр. Пловдив, ул. Шести септември 3', 'Стефан Илиев', '+359888777888'],
            'pathstroy' => ['Пътстрой Юг ЕООД', '205666222', 'гр. Благоевград, ул. Тодор Александров 2', 'Николай Стоянов', '+359888999000'],
        ];

        foreach ($rows as $key => [$name, $eik, $address, $owner, $phone]) {
            $this->companies[$key] = Company::updateOrCreate(
                ['eik' => $eik],
                [
                    'public_id' => PublicIdGenerator::generate(),
                    'name' => $name,
                    'address' => $address,
                    'owner_name' => $owner,
                    'phone' => $phone,
                    'source_url' => 'https://portal.registryagency.bg/CR/en/Reports/VerificationPersonOrg?guid='.$eik,
                ],
            );
        }
    }

    /**
     * The hero case: a laptop whose price creeps 1400 → 3300 BGN across the year, with
     * one tender far above the line. Builds the price-over-time series + a price_discrepancy
     * flag that links to it (series_key).
     */
    private function seedLaptopOverpricing(): void
    {
        $authority = $this->authorities['burgas'];
        $winner = $this->companies['tehnotrade'];

        $tender = $this->tender('2026/S-100100', $authority, $winner, [
            'title' => 'Доставка на преносими компютри за нуждите на общинската администрация',
            'description' => 'Доставка на 50 лаптопа 15.6" i5/16GB RAM за административни нужди.',
            'cpv_code' => '30213100',
            'value' => 412500.00,
            'status' => TenderStatus::Awarded,
            'announced_at' => '2026-04-01',
            'awarded_at' => '2026-05-10',
        ]);

        $item = TenderItem::create([
            'public_id' => PublicIdGenerator::generate(),
            'tender_id' => $tender->id,
            'description' => 'Лаптоп 15.6" i5 / 16GB RAM',
            'quantity' => 50,
            'unit' => 'бр.',
            'unit_price' => 3300.00,
            'currency' => 'BGN',
            'vat_included' => true,
            'source_url' => $tender->source_url,
        ]);

        // The market curve: six point-in-time captures climbing well past a fair price.
        $prices = [1400, 1780, 2160, 2540, 2920, 3300];
        foreach ($prices as $i => $price) {
            PriceSnapshot::create([
                'public_id' => PublicIdGenerator::generate(),
                'tender_item_id' => $item->id,
                'product_key' => self::SERIES_KEY,
                'description' => 'Лаптоп 15.6" i5 / 16GB RAM',
                'price' => $price,
                'currency' => 'BGN',
                'captured_at' => Carbon::parse('2026-06-05')->subDays((5 - $i) * 25),
                'source_url' => 'https://ted.europa.eu/udl?uri=TED:NOTICE:'.(100100 + $i).'-2026',
            ]);
        }

        $this->flag([
            'type' => FlagType::PriceDiscrepancy,
            'score' => 88,
            'title' => 'Община Бургас надплати 2.4× за същия лаптоп',
            'explanation_bg' => 'Същият модел лаптоп (15.6" i5/16GB) е поръчан на 3300 лв./бр., докато средната пазарна цена в други поръчки за периода е около 1400 лв./бр. — разлика от 2.4 пъти без обосновка в документацията.',
            'sphere' => Sphere::Police,
            'category' => CorruptionCategory::PublicProcurement,
            'series_key' => self::SERIES_KEY,
            'subject' => $tender,
            'authority' => $authority,
            'evidence' => [
                ['label' => 'Платена цена/бр.', 'value' => '3 300 лв.', 'money' => ['amount' => 3300, 'currency' => 'BGN', 'vat_included' => true]],
                ['label' => 'Пазарна цена/бр.', 'value' => '1 400 лв.', 'money' => ['amount' => 1400, 'currency' => 'BGN', 'vat_included' => true]],
                ['label' => 'Надплащане', 'value' => '2.4×'],
                ['label' => 'Брой', 'value' => 50],
            ],
            'source_urls' => [$tender->source_url, 'https://ted.europa.eu/udl?uri=TED:NOTICE:100105-2026'],
        ]);
    }

    /**
     * The serial-winner / shell-cluster case: one company wins streak after streak from
     * the same authorities, with a second company sharing its address/owner.
     */
    private function seedSerialWinner(): void
    {
        $winner = $this->companies['stroyinvest'];
        $shell = $this->companies['buildpro'];

        // Стройинвест: 6 wins in Бургас, 3 in Пловдив.
        $this->winStreak($winner, $this->authorities['burgas'], 6, 'BG341', 500100, 'Ремонт на улична мрежа — обособена позиция');
        $this->winStreak($winner, $this->authorities['plovdiv'], 3, 'BG421', 500200, 'Текущ ремонт на тротоари');
        // The shell: 4 more wins from the same Бургас authority.
        $this->winStreak($shell, $this->authorities['burgas'], 4, 'BG341', 500300, 'Поддръжка на общински сгради');

        $this->flag([
            'type' => FlagType::SerialWinner,
            'score' => 79,
            'title' => 'Стройинвест Корект печели 9 поредни поръчки',
            'explanation_bg' => 'Стройинвест Корект ЕООД печели 9 обществени поръчки от два възложителя, а свързаната фирма Билд Про Груп ЕООД (същ. адрес и управител) печели още 4 от същия възложител — индикация за разпределяне на пазара между свързани лица.',
            'sphere' => Sphere::Police,
            'category' => CorruptionCategory::PublicProcurement,
            'subject' => $winner,
            'authority' => $this->authorities['burgas'],
            'sector' => 'roads',
            'evidence' => [
                ['label' => 'Спечелени поръчки', 'value' => 9],
                ['label' => 'Свързана фирма', 'value' => 'Билд Про Груп ЕООД'],
                ['label' => 'Общ обем', 'value' => '4.2 млн. лв.', 'money' => ['amount' => 4200000, 'currency' => 'BGN', 'vat_included' => true]],
            ],
            'source_urls' => ['https://app.eop.bg/buyer/000056814', 'https://portal.registryagency.bg/CR/en/Reports/VerificationPersonOrg?guid=200111222'],
        ]);
    }

    /**
     * Breadth so the feed + map look alive: one flag per remaining type, spread across
     * spheres and regions. Each is attached to a real tender so it carries an authority,
     * region, and sector.
     */
    private function seedBreadthFlags(): void
    {
        $cases = [
            [
                'type' => FlagType::TailoredSpec, 'score' => 72, 'sphere' => Sphere::Healthcare,
                'auth' => 'umbal', 'winner' => 'medtech', 'cpv' => '33141000', 'value' => 980000,
                'title' => 'Спецификация, скроена за един доставчик',
                'tender_title' => 'Доставка на медицински консумативи',
                'explanation' => 'Техническата спецификация изисква комбинация от характеристики, на която отговаря само един продукт на пазара — класически признак за нагласена поръчка.',
                'evidence' => [['label' => 'Отговарящи оферти', 'value' => 1], ['label' => 'Прогнозна стойност', 'value' => '980 000 лв.', 'money' => ['amount' => 980000, 'currency' => 'BGN', 'vat_included' => true]]],
            ],
            [
                'type' => FlagType::Cancelled, 'score' => 64, 'sphere' => Sphere::Judiciary,
                'auth' => 'vss', 'winner' => null, 'cpv' => '30200000', 'value' => 350000,
                'status' => TenderStatus::Cancelled,
                'title' => 'Прекратена след отваряне на офертите',
                'tender_title' => 'Доставка на компютърна техника за съдилища',
                'explanation' => 'Поръчката е прекратена от възложителя след отварянето на офертите и обявена отново с променени условия — модел, при който „неудобният" участник отпада.',
                'evidence' => [['label' => 'Статус', 'value' => 'Прекратена'], ['label' => 'Дни до прекратяване', 'value' => 12]],
            ],
            [
                'type' => FlagType::ImplausibleScope, 'score' => 91, 'sphere' => Sphere::Police,
                'auth' => 'belitsa', 'winner' => 'pathstroy', 'cpv' => '45233140', 'value' => 1200000,
                'title' => 'Ремонт на път, който е почти нов',
                'tender_title' => 'Ремонт на общински път BGS1083',
                'explanation' => 'Възложен е „основен ремонт" на отсечка, изградена преди 2 години, при който се подменят само 2 пласта — физически и финансово необоснован обхват спрямо състоянието на актива.',
                'evidence' => [['label' => 'Възраст на пътя', 'value' => '2 г.'], ['label' => 'Стойност', 'value' => '1.2 млн. лв.', 'money' => ['amount' => 1200000, 'currency' => 'BGN', 'vat_included' => true]]],
            ],
            [
                'type' => FlagType::DelayedPayment, 'score' => 47, 'sphere' => Sphere::Healthcare,
                'auth' => 'varna', 'winner' => 'medtech', 'cpv' => '85100000', 'value' => 640000,
                'title' => 'Хронично забавени плащания',
                'tender_title' => 'Услуги по дезинфекция на общински обекти',
                'explanation' => 'Между договаряне и реално плащане минават средно 180 дни — двойно над договорения срок, което натоварва изпълнителя и буди въпроси за избирателно третиране.',
                'evidence' => [['label' => 'Среден лаг', 'value' => '180 дни'], ['label' => 'Договорен срок', 'value' => '90 дни']],
            ],
            [
                'type' => FlagType::DocClone, 'score' => 38, 'sphere' => Sphere::Judiciary,
                'auth' => 'sofia', 'winner' => 'itsol', 'cpv' => '72000000', 'value' => 420000,
                'title' => 'Копирана документация с вмъкната клауза',
                'tender_title' => 'Поддръжка на информационни системи',
                'explanation' => 'Документацията е почти идентична със стандартен образец, но с вмъкната клауза за специфичен сертификат, който стеснява кръга на кандидатите.',
                'evidence' => [['label' => 'Сходство с образец', 'value' => '96%'], ['label' => 'Добавени клаузи', 'value' => 1]],
            ],
            [
                'type' => FlagType::PriceDiscrepancy, 'score' => 58, 'sphere' => Sphere::Healthcare,
                'auth' => 'plovdiv', 'winner' => 'itsol', 'cpv' => '30190000', 'value' => 210000,
                'title' => 'Офис техника на завишени цени',
                'tender_title' => 'Доставка на офис оборудване',
                'explanation' => 'Единичните цени на стандартно офис оборудване са с около 60% над съпоставими поръчки за същия период.',
                'evidence' => [['label' => 'Отклонение', 'value' => '+60%'], ['label' => 'Стойност', 'value' => '210 000 лв.', 'money' => ['amount' => 210000, 'currency' => 'BGN', 'vat_included' => true]]],
            ],
        ];

        $seq = 600100;
        foreach ($cases as $c) {
            $authority = $this->authorities[$c['auth']];
            $winner = $c['winner'] !== null ? $this->companies[$c['winner']] : null;

            $tender = $this->tender('2026/S-'.$seq++, $authority, $winner, [
                'title' => $c['tender_title'],
                'description' => $c['explanation'],
                'cpv_code' => $c['cpv'],
                'value' => $c['value'],
                'status' => $c['status'] ?? ($winner !== null ? TenderStatus::Awarded : TenderStatus::Announced),
                'announced_at' => '2026-03-15',
                'awarded_at' => $winner !== null ? '2026-04-20' : null,
            ]);

            $this->flag([
                'type' => $c['type'],
                'score' => $c['score'],
                'title' => $c['title'],
                'explanation_bg' => $c['explanation'],
                'sphere' => $c['sphere'],
                'category' => CorruptionCategory::PublicProcurement,
                'subject' => $tender,
                'authority' => $authority,
                'evidence' => $c['evidence'],
                'source_urls' => [$tender->source_url],
            ]);
        }
    }

    /** Create a streak of awarded tenders for a company from one authority. */
    private function winStreak(Company $winner, ContractingAuthority $authority, int $count, string $region, int $startSeq, string $title): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->tender('2026/S-'.($startSeq + $i), $authority, $winner, [
                'title' => $title.' № '.($i + 1),
                'description' => 'Строително-монтажни работи по обособена позиция.',
                'cpv_code' => '45233140',
                'value' => 350000 + $i * 25000,
                'status' => TenderStatus::Awarded,
                'announced_at' => '2026-02-01',
                'awarded_at' => '2026-03-01',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function tender(string $naturalKey, ContractingAuthority $authority, ?Company $winner, array $attrs): Tender
    {
        return Tender::updateOrCreate(
            ['source' => 'demo', 'natural_key' => $naturalKey],
            array_merge([
                'public_id' => PublicIdGenerator::generate(),
                'source_url' => 'https://ted.europa.eu/udl?uri=TED:NOTICE:'.str_replace(['2026/S-', '/'], '', $naturalKey).'-2026',
                'fetched_at' => now(),
                'contracting_authority_id' => $authority->id,
                'winner_company_id' => $winner?->id,
                'sphere' => null,
                'category' => CorruptionCategory::PublicProcurement,
                'currency' => 'BGN',
                'vat_included' => true,
            ], $attrs),
        );
    }

    /**
     * Create a flag, denormalizing sector + region_code from its subject for the
     * read-optimized feed/map. `subject` is a Tender, Company, or ContractingAuthority.
     *
     * @param  array<string, mixed>  $a
     */
    private function flag(array $a): void
    {
        /** @var Tender|Company|ContractingAuthority $subject */
        $subject = $a['subject'];
        /** @var ContractingAuthority|null $authority */
        $authority = $a['authority'] ?? null;

        $cpv = $subject instanceof Tender ? $subject->cpv_code : null;
        $sector = $a['sector'] ?? SectorResolver::fromCpv($cpv);
        $regionCode = $authority?->region;
        $score = (int) $a['score'];

        Flag::create([
            'public_id' => PublicIdGenerator::generate(),
            'type' => $a['type'],
            'sphere' => $a['sphere'] ?? null,
            'category' => $a['category'] ?? CorruptionCategory::PublicProcurement,
            'score' => $score,
            'severity' => FlagSeverity::fromScore($score),
            'title' => $a['title'] ?? null,
            'series_key' => $a['series_key'] ?? null,
            'sector' => $sector,
            'region_code' => $regionCode,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->id,
            'subject_label' => $subject instanceof Tender ? $subject->title : ($subject->name ?? null),
            'explanation_bg' => $a['explanation_bg'],
            'source_urls' => $a['source_urls'],
            'evidence' => $a['evidence'],
            'detected_at' => now()->subDays(random_int(0, 20)),
            // Demo popularity: juicier (higher-score) flags read as more-viewed.
            'view_count' => $score * random_int(8, 40),
        ]);
    }
}
