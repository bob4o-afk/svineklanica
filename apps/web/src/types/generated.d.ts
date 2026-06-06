declare namespace App.Shared.Enums {
export enum CorruptionCategory { PublicProcurement = 5000, UnregulatedPayment = 5010 };
export enum FlagSeverity { Low = 3000, Medium = 3010, High = 3020, Critical = 3030 };
export enum Sphere { Judiciary = 6000, Healthcare = 6010, Police = 6020, Education = 6030 };
}
declare namespace Modules.Detection.Data {
export type FlagData = {
publicId: string;
type: Modules.Detection.Enums.FlagType;
sphere: App.Shared.Enums.Sphere | null;
category: App.Shared.Enums.CorruptionCategory | null;
score: number;
severity: App.Shared.Enums.FlagSeverity;
subjectType: string;
subjectLabel: string | null;
explanationBg: string;
sourceUrls: string[];
evidence: Record<string, unknown> | null;
detectedAt: string | null;
};
export type FlagFilterData = {
sphere: App.Shared.Enums.Sphere | null;
category: App.Shared.Enums.CorruptionCategory | null;
severity: App.Shared.Enums.FlagSeverity | null;
type: Modules.Detection.Enums.FlagType | null;
minScore: number | null;
perPage: number;
};
}
declare namespace Modules.Detection.Enums {
export enum FlagType { PriceDiscrepancy = 2000, TailoredSpec = 2010, SerialWinner = 2020, Cancelled = 2030, ImplausibleScope = 2040, DelayedPayment = 2050, DocClone = 2060 };
}
declare namespace Modules.Identity.Data {
export type LoginData = {
email: string;
password: string;
};
export type UserData = {
publicId: string;
name: string;
email: string;
isAdmin: boolean;
};
export type WhitelistEntryData = {
value: string;
source: string;
};
}
declare namespace Modules.Notifications.Data {
export type BroadcastData = {
subject: string;
lines: string[];
};
export type SubscribeData = {
email: string;
};
}
declare namespace Modules.Procurement.Enums {
export enum TenderStatus { Announced = 1000, Open = 1010, Awarded = 1020, Cancelled = 1030, Terminated = 1040 };
}
declare namespace Modules.Publishing.Data {
export type PostData = {
publicId: string;
title: string;
slug: string;
excerpt: string | null;
body: string;
status: Modules.Publishing.Enums.PostStatus;
sphere: App.Shared.Enums.Sphere | null;
category: App.Shared.Enums.CorruptionCategory | null;
severity: App.Shared.Enums.FlagSeverity | null;
tags: number[];
viewCount: number;
authorName: string | null;
sourceUrls: string[];
publishedAt: string | null;
};
export type PostFilterData = {
sphere: App.Shared.Enums.Sphere | null;
category: App.Shared.Enums.CorruptionCategory | null;
severity: App.Shared.Enums.FlagSeverity | null;
tag: Modules.Publishing.Enums.PostTag | null;
perPage: number;
};
export type StorePostData = {
title: string;
excerpt: string | null;
body: string;
sphere: App.Shared.Enums.Sphere | null;
category: App.Shared.Enums.CorruptionCategory | null;
severity: App.Shared.Enums.FlagSeverity | null;
tags: number[] | null;
sourceUrls: string[] | null;
};
export type UpdatePostData = {
title: string;
excerpt: string | null;
body: string;
status: Modules.Publishing.Enums.PostStatus;
sphere: App.Shared.Enums.Sphere | null;
category: App.Shared.Enums.CorruptionCategory | null;
severity: App.Shared.Enums.FlagSeverity | null;
tags: number[] | null;
sourceUrls: string[] | null;
};
}
declare namespace Modules.Publishing.Enums {
export enum PostStatus { Draft = 4000, Published = 4010, Archived = 4020 };
export enum PostTag { StealingMoney = 7000, DodgyDeals = 7010, ShadyBusiness = 7020 };
}
