import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import about from './locales/bg/about.json';
import common from './locales/bg/common.json';
import errors from './locales/bg/errors.json';
import feed from './locales/bg/feed.json';
import flags from './locales/bg/flags.json';
import home from './locales/bg/home.json';
import post from './locales/bg/post.json';

export const defaultNS = 'common';

export const resources = {
  bg: { common, home, feed, flags, post, errors, about },
} as const;

void i18n.use(initReactI18next).init({
  lng: 'bg',
  fallbackLng: 'bg',
  defaultNS,
  ns: ['common', 'home', 'feed', 'flags', 'post', 'errors', 'about'],
  resources,
  interpolation: { escapeValue: false },
  returnNull: false,
});

export default i18n;
