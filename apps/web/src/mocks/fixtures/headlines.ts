import type { FlagType } from '@/types/api';

interface HeadlineInput {
  authority: string;
  company?: string;
  multiplier: number;
  count: number;
}

/** Punk, punchy Bulgarian headlines per detector. The factual body stays neutral. */
export function makeHeadline(type: FlagType, i: HeadlineInput): string {
  switch (type) {
    case 'price_discrepancy':
      return `${i.authority} надплати ${i.multiplier}× за същата стока`;
    case 'serial_winner':
      return `${i.company ?? 'Една фирма'} печели ${i.count} поредни поръчки от ${i.authority}`;
    case 'tailored_spec':
      return `Поръчка, скроена за един: ${i.authority} стесни условията`;
    case 'cancelled':
      return `${i.authority} прекрати поръчката след отварянето на офертите`;
    case 'implausible_scope':
      return `„Ремонт" на нов обект: числата при ${i.authority} не се връзват`;
    case 'delayed_payment':
      return `${i.authority} бави плащанията към изпълнителите`;
    case 'doc_clone':
      return `Преписана документация в полза на ${i.company ?? 'един участник'}`;
  }
}

/** Neutral, factual explanation per detector (the credible body under the punk headline). */
export function makeExplanation(type: FlagType, i: HeadlineInput): string {
  switch (type) {
    case 'price_discrepancy':
      return `Същата стока е поръчана на цена около ${i.multiplier} пъти по-висока от друга поръчка за същия период. Разликата не се обяснява с количество или спецификация.`;
    case 'serial_winner':
      return `${i.company ?? 'Една фирма'} е спечелила ${i.count} поредни поръчки от ${i.authority}. Моделът заслужава проверка за конкуренция.`;
    case 'tailored_spec':
      return `Изискванията в поръчката са необичайно тесни спрямо нормата за категорията, така че на практика само един участник отговаря.`;
    case 'cancelled':
      return `Поръчката е прекратена от възложителя след отварянето на офертите и впоследствие обявена отново с променени условия.`;
    case 'implausible_scope':
      return `Заявеният обем или единични цени не съответстват на състоянието на обекта спрямо предишни договори за същия обект.`;
    case 'delayed_payment':
      return `Договорените срокове за плащане се разминават системно с реалните плащания към изпълнителите.`;
    case 'doc_clone':
      return `Документацията е почти идентична със стандартен образец, но съдържа клауза, която стеснява кръга на допустимите участници.`;
  }
}
