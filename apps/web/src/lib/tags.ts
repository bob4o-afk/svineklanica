import { CoinsIcon, type Icon, HandshakeIcon, MaskHappyIcon } from '@phosphor-icons/react';
import type { PunkTag } from '@/types/api';

/** Single source for how each editorial PUNK tag is presented (icon + i18n label).
 *  These are the "шуши-муши" badges (CLAUDE.md §1.0.1) — the roast on top of the data. */
interface PunkTagMeta {
  icon: Icon;
  i18nKey: string;
}

export const punkTagMeta: Record<PunkTag, PunkTagMeta> = {
  theft: { icon: CoinsIcon, i18nKey: 'flags:tag.theft' },
  dodgy_deal: { icon: HandshakeIcon, i18nKey: 'flags:tag.dodgy_deal' },
  shushi_mushi: { icon: MaskHappyIcon, i18nKey: 'flags:tag.shushi_mushi' },
};

/** Display / picker order. */
export const ALL_PUNK_TAGS: readonly PunkTag[] = ['theft', 'dodgy_deal', 'shushi_mushi'];
