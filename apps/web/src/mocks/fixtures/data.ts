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
  RegionAggregate,
  SerialWinnerGraph,
  SourceRef,
  TenderRef,
} from '@/types/api';
import { daysAgoISO, intBetween, mulberry32, pick } from './factory';
import { makeExplanation, makeHeadline } from './headlines';

export const authorities: AuthorityRef[] = [
  { public_id: 'auth-1', name: 'Община Старо Корито', region_code: 'BG-DEMO-01' },
  { public_id: 'auth-2', name: 'Община Бели бряг', region_code: 'BG-DEMO-02' },
  { public_id: 'auth-3', name: 'Областна управа Среднево', region_code: 'BG-DEMO-03' },
  { public_id: 'auth-4', name: 'Община Долна вода', region_code: 'BG-DEMO-02' },
  { public_id: 'auth-5', name: 'Държавна агенция за обществени имоти', region_code: 'BG-DEMO-01' },
  { public_id: 'auth-6', name: 'Община Горни ливади', region_code: 'BG-DEMO-04' },
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

function buildFlag(index: number, rng: () => number): FlagPost {
  const type = pick(WEIGHTED_TYPES, rng());
  const severity = pick(WEIGHTED_SEVERITIES, rng());
  const authority = pick(authorities, rng());
  const company = pick(companies, rng());
  const tender = pick(tenders, rng());
  const multiplier = intBetween(2, 12, rng());
  const count = intBetween(4, 11, rng());
  const amount = intBetween(50_000, 4_000_000, rng());
  const detectedAt = daysAgoISO(index);
  const approved = index < APPROVED_COUNT;

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
    severity,
    status: approved ? 'approved' : 'pending',
    subject: buildSubject(type, authority, company, tender),
    title: makeHeadline(type, headlineInput),
    explanation_bg: makeExplanation(type, headlineInput),
    evidence,
    sources,
    detected_at: detectedAt,
    ...(approved ? { published_at: detectedAt } : {}),
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

// --- Region aggregates (placeholder codes until the Phase-3 topojson scheme is agreed) ---
export const regionAggregates: RegionAggregate[] = [
  { region_code: 'BG-DEMO-01', region_name: 'Среднево-Запад', metric: 12, flag_count: 12 },
  { region_code: 'BG-DEMO-02', region_name: 'Беломорие', metric: 8, flag_count: 8 },
  { region_code: 'BG-DEMO-03', region_name: 'Среднево-Изток', metric: 5, flag_count: 5 },
  { region_code: 'BG-DEMO-04', region_name: 'Горноречие', metric: 3, flag_count: 3 },
];
