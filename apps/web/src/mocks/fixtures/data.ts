/** Entirely INVENTED but realistic demo data — no real institutions or companies.
 *  Deterministic (seeded) so tests are stable. A „демо данни" banner marks it in the UI. */
import type {
  AuthorityRef,
  CompanyRef,
  EvidenceItem,
  FlagPost,
  FlagSeverity,
  FlagSubject,
  FlagType,
  PriceSeries,
  PunkTag,
  SerialWinnerGraph,
  Source,
  SourceRef,
  TenderRef,
} from '@/types/api';
import { sectorFromCpv } from '@/lib/sectors';
import { daysAgoISO, intBetween, mulberry32, pick } from './factory';
import { makeExplanation, makeHeadline } from './headlines';

export const authorities: AuthorityRef[] = [
  { public_id: 'auth-1', name: 'Община Старо Корито', region_code: 'BG411' },
  { public_id: 'auth-2', name: 'Община Бели бряг', region_code: 'BG421' },
  { public_id: 'auth-3', name: 'Областна управа Среднево', region_code: 'BG331' },
  { public_id: 'auth-4', name: 'Община Долна вода', region_code: 'BG341' },
  { public_id: 'auth-5', name: 'Държавна агенция за обществени имоти', region_code: 'BG412' },
  { public_id: 'auth-6', name: 'Община Горни ливади', region_code: 'BG314' },
];

export const companies: CompanyRef[] = [
  { public_id: 'comp-1', eik: '200111222', name: 'Стройинвест Корект ЕООД' },
  { public_id: 'comp-2', eik: '201333444', name: 'Алфа Билд Груп АД' },
  { public_id: 'comp-3', eik: '202555666', name: 'Техно Сервиз 2019 ЕООД' },
  { public_id: 'comp-4', eik: '203777888', name: 'Пътно Дело БГ ЕООД' },
  { public_id: 'comp-5', eik: '204999000', name: 'Глобал Тендер ООД' },
  { public_id: 'comp-6', eik: '205112233', name: 'Мега Конструкт ЕООД' },
  { public_id: 'comp-7', eik: '206445566', name: 'Бялата Лопата ЕООД' },
  { public_id: 'comp-8', eik: '207778899', name: 'Север Строй ЕООД' },
];

export const tenders: TenderRef[] = [
  { public_id: 'tend-1', title: 'Доставка на лаптопи и компютърна техника', cpv_code: '30213100' },
  { public_id: 'tend-2', title: 'Ремонт на общински път', cpv_code: '45233142' },
  { public_id: 'tend-3', title: 'Изграждане на детска площадка', cpv_code: '37535200' },
  { public_id: 'tend-4', title: 'Доставка на медицинско оборудване', cpv_code: '33100000' },
  { public_id: 'tend-5', title: 'Зимно поддържане на пътища', cpv_code: '90620000' },
  { public_id: 'tend-6', title: 'Реконструкция на водопровод', cpv_code: '45231300' },
  { public_id: 'tend-7', title: 'Образователни услуги и обучения', cpv_code: '80500000' },
  { public_id: 'tend-8', title: 'Изграждане на спортна зала', cpv_code: '45212200' },
  { public_id: 'tend-9', title: 'Доставка на хранителни продукти', cpv_code: '15800000' },
];

const WEIGHTED_TYPES: FlagType[] = [
  'price_discrepancy',
  'price_discrepancy',
  'price_discrepancy',
  'serial_winner',
  'serial_winner',
  'serial_winner',
  'tailored_spec',
  'cancelled',
  'implausible_scope',
  'delayed_payment',
  'doc_clone',
];

const WEIGHTED_SEVERITIES: FlagSeverity[] = [
  'critical',
  'high',
  'high',
  'medium',
  'medium',
  'low',
];

const TOTAL = 30;
const APPROVED_COUNT = 24;

function buildSubject(
  type: FlagType,
  authority: AuthorityRef,
  company: CompanyRef,
  tender: TenderRef,
): FlagSubject {
  if (type === 'serial_winner' || type === 'doc_clone') {
    return { type: 'company', authority, company };
  }
  return { type: 'tender', authority, tender };
}

/** Editorial punk tags an editor would slap on at publish time (CLAUDE.md §1.0.1). Deterministic
 *  from type/severity so approved fixtures already show the "шуши-муши" layer in the feed. */
function tagsFor(type: FlagType, severity: FlagSeverity): PunkTag[] {
  const tags: PunkTag[] = [];
  if (type === 'price_discrepancy' || type === 'implausible_scope' || type === 'delayed_payment') {
    tags.push('theft');
  }
  if (type === 'serial_winner' || type === 'cancelled' || type === 'tailored_spec') {
    tags.push('dodgy_deal');
  }
  if (type === 'doc_clone' || severity === 'critical') tags.push('shushi_mushi');
  return tags;
}

function buildFlag(index: number, rng: () => number): FlagPost {
  const type = pick(WEIGHTED_TYPES, rng());
  const severity = pick(WEIGHTED_SEVERITIES, rng());
  const authority = pick(authorities, rng());
  const company = pick(companies, rng());
  const isPriceFlag = type === 'price_discrepancy';
  const randomTender = pick(tenders, rng());
  // Price-discrepancy flags tell the laptop "price creep" story, so they link to that series.
  const tender = isPriceFlag ? (tenders[0] ?? randomTender) : randomTender;
  const multiplier = intBetween(2, 12, rng());
  const count = intBetween(4, 11, rng());
  const amount = intBetween(50_000, 4_000_000, rng());
  const detectedAt = daysAgoISO(index);
  const approved = index < APPROVED_COUNT;
  const tags = tagsFor(type, severity);

  const headlineInput = { authority: authority.name, company: company.name, multiplier, count };

  const evidence: EvidenceItem[] = [
    { label: 'Договорена стойност', value: amount, money: { amount, currency: 'BGN', vat_included: true } },
    type === 'serial_winner'
      ? { label: 'Поредни поръчки', value: count }
      : { label: 'Кратност на цената', value: `${multiplier}×` },
  ];

  const sources: SourceRef[] = [
    {
      url: `https://demo.eop.example/tender/${tender.public_id}`,
      label: 'Профил на купувача (демо)',
      fetched_at: detectedAt,
    },
  ];

  return {
    public_id: `flag-${index + 1}`,
    type,
    category: sectorFromCpv(tender.cpv_code),
    severity,
    status: approved ? 'approved' : 'pending',
    subject: buildSubject(type, authority, company, tender),
    title: makeHeadline(type, headlineInput),
    explanation_bg: makeExplanation(type, headlineInput),
    evidence,
    sources,
    detected_at: detectedAt,
    ...(approved ? { published_at: detectedAt } : {}),
    ...(isPriceFlag ? { series_key: 'laptops' } : {}),
    ...(approved && tags.length > 0 ? { tags } : {}),
  };
}

const rng = mulberry32(1337);
export const flagPosts: FlagPost[] = Array.from({ length: TOTAL }, (_, i) => buildFlag(i, rng));

export const approvedFlags: FlagPost[] = flagPosts.filter((f) => f.status === 'approved');
export const pendingFlags: FlagPost[] = flagPosts.filter((f) => f.status === 'pending');

// --- One curated price series (the "price creep" story) ---
const laptopVendors = [companies[0], companies[2], companies[4]] as const;
export const priceSeriesByKey: Record<string, PriceSeries> = {
  laptops: {
    series_key: 'laptops',
    product_label: 'Лаптоп 15" i5 / 16GB RAM',
    cpv_code: '30213100',
    unit: 'бр.',
    points: Array.from({ length: 6 }, (_, i) => {
      const vendor = laptopVendors[i % laptopVendors.length];
      const unitPrice = 1400 + i * 380;
      return {
        captured_at: daysAgoISO(150 - i * 25),
        price: { amount: unitPrice, currency: 'BGN' as const, vat_included: true },
        source: {
          url: `https://demo.eop.example/price/laptops/${i}`,
          label: 'Поръчка (демо)',
          fetched_at: daysAgoISO(150 - i * 25),
        },
        ...(vendor ? { vendor } : {}),
      };
    }),
  },
};

// --- One curated serial-winner graph ---
export const serialWinnerGraphById: Record<string, SerialWinnerGraph> = {
  'comp-1': {
    nodes: [
      { id: 'comp-1', kind: 'company', label: 'Стройинвест Корект ЕООД', public_id: 'comp-1', eik: '200111222', win_count: 9 },
      { id: 'comp-6', kind: 'company', label: 'Мега Конструкт ЕООД', public_id: 'comp-6', eik: '205112233', win_count: 4, cluster_id: 'c1' },
      { id: 'auth-1', kind: 'authority', label: 'Община Старо Корито', public_id: 'auth-1' },
      { id: 'auth-3', kind: 'authority', label: 'Областна управа Среднево', public_id: 'auth-3' },
    ],
    edges: [
      { id: 'e1', source: 'comp-1', target: 'auth-1', weight: 6, label: '6 поръчки' },
      { id: 'e2', source: 'comp-1', target: 'auth-3', weight: 3, label: '3 поръчки' },
      { id: 'e3', source: 'comp-6', target: 'auth-1', weight: 4, label: '4 поръчки' },
    ],
  },
};

// Region aggregates are computed dynamically in the MSW handler from the flags above
// (grouped by the authority's region_code, optionally filtered by sector) — see mocks/handlers.

// --- Data sources (the admin „Източници" registry — mirrors SOURCES.md / data-sources.md) ---
export const sourceSeed: Source[] = [
  {
    public_id: 'src-1',
    key: 'ted',
    label: 'TED — Tenders Electronic Daily',
    base_url: 'https://ted.europa.eu',
    enabled: true,
    last_ingested_at: daysAgoISO(1),
    notes: 'Структуриран bulk източник без вход — демо приоритет.',
  },
  {
    public_id: 'src-2',
    key: 'egov',
    label: 'data.egov.bg — Портал за отворени данни',
    base_url: 'https://data.egov.bg',
    enabled: true,
    last_ingested_at: daysAgoISO(3),
  },
  {
    public_id: 'src-3',
    key: 'aop',
    label: 'АОП / РОП — Регистър на обществените поръчки',
    base_url: 'https://aop.bg',
    enabled: false,
    notes: 'Историческа дълбочина; за скрейпване по-късно.',
  },
  {
    public_id: 'src-4',
    key: 'sebra',
    label: 'СЕБРА — бюджетни разплащания',
    base_url: 'https://minfin.bg',
    enabled: false,
    notes: 'Захранва детектора „забавени плащания".',
  },
];
