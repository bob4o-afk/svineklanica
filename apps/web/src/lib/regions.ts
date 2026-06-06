/** NUTS3 (oblast) code → Bulgarian name, for the region map + aggregates. 28 provinces.
 *  Codes match `properties.NUTS_ID` in public/geo/bg-provinces.geojson (Eurostat GISCO NUTS3). */
export const REGION_NAMES: Record<string, string> = {
  BG311: 'Видин',
  BG312: 'Монтана',
  BG313: 'Враца',
  BG314: 'Плевен',
  BG315: 'Ловеч',
  BG321: 'Велико Търново',
  BG322: 'Габрово',
  BG323: 'Русе',
  BG324: 'Разград',
  BG325: 'Силистра',
  BG331: 'Варна',
  BG332: 'Добрич',
  BG333: 'Шумен',
  BG334: 'Търговище',
  BG341: 'Бургас',
  BG342: 'Сливен',
  BG343: 'Ямбол',
  BG344: 'Стара Загора',
  BG411: 'София (град)',
  BG412: 'София (област)',
  BG413: 'Благоевград',
  BG414: 'Перник',
  BG415: 'Кюстендил',
  BG421: 'Пловдив',
  BG422: 'Пазарджик',
  BG423: 'Смолян',
  BG424: 'Хасково',
  BG425: 'Кърджали',
};

export function regionName(code: string): string {
  return REGION_NAMES[code] ?? code;
}
