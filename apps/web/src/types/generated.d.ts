declare namespace Modules.Detection.Enums {
export enum FlagSeverity { Low = 3000, Medium = 3010, High = 3020, Critical = 3030 };
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
viewCount: number;
authorName: string | null;
sourceUrls: string[];
publishedAt: string | null;
};
export type StorePostData = {
title: string;
excerpt: string | null;
body: string;
sourceUrls: string[] | null;
};
export type UpdatePostData = {
title: string;
excerpt: string | null;
body: string;
status: Modules.Publishing.Enums.PostStatus;
sourceUrls: string[] | null;
};
}
declare namespace Modules.Publishing.Enums {
export enum PostStatus { Draft = 4000, Published = 4010, Archived = 4020 };
}
