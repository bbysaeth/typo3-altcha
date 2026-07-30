import "altcha/i18n";

/**
 * ALTCHA v3 dropped the widget's `strings` attribute, so custom texts have to go
 * into the global i18n store instead. The widget reads that store reactively,
 * which means a registration that happens after the widget was upgraded still
 * updates the rendered labels.
 *
 * The texts are handed over as JSON in the `data-altcha-i18n` attribute of the
 * widget, rendered by the AltchaTranslations partial.
 */
const OVERRIDES_ATTRIBUTE = "data-altcha-i18n";
const FALLBACK_LOCALE = "en";

/**
 * Reads a Svelte store synchronously. Subscribing invokes the callback right
 * away, so unsubscribing immediately leaves us with the current value.
 */
function readStore(store) {
  let value;
  const unsubscribe = store.subscribe((current) => {
    value = current;
  });
  unsubscribe();

  return value;
}

/**
 * Mirrors the widget's own language resolution: the first candidate that either
 * matches a registered locale exactly or shares its primary subtag wins.
 */
function resolveLocale(locales, candidates) {
  const codes = Object.keys(locales);

  for (const candidate of candidates) {
    if (!candidate) {
      continue;
    }

    const language = candidate.toLowerCase();

    if (locales[language]) {
      return language;
    }

    const match = codes.find(
      (code) => language.split("-")[0] === code.split("-")[0]
    );

    if (match) {
      return match;
    }
  }

  return FALLBACK_LOCALE;
}

function applyTranslationOverrides() {
  const element = document.querySelector(
    "altcha-widget[" + OVERRIDES_ATTRIBUTE + "]"
  );
  const i18n = globalThis.$altcha && globalThis.$altcha.i18n;

  if (!element || !i18n) {
    return;
  }

  let overrides;
  try {
    overrides = JSON.parse(element.getAttribute(OVERRIDES_ATTRIBUTE));
  } catch {
    return;
  }

  // The partial shipped with the extension renders `null` until someone
  // overrides it, so anything that is not a populated object is ignored.
  if (
    !overrides ||
    typeof overrides !== "object" ||
    Array.isArray(overrides) ||
    Object.keys(overrides).length === 0
  ) {
    return;
  }

  const locales = readStore(i18n.store) || {};
  // Resolve before writing, otherwise a newly created key could shift the
  // resolution the widget performs afterwards.
  const locale = resolveLocale(locales, [
    element.getAttribute("language"),
    document.documentElement.lang,
    ...navigator.languages,
  ]);

  // The widget returns the resolved locale as is and does not merge it with a
  // base locale. Partial overrides therefore have to be merged here, or every
  // string they omit would end up undefined.
  i18n.set(locale, { ...locales[locale], ...overrides });
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", applyTranslationOverrides, {
    once: true,
  });
} else {
  applyTranslationOverrides();
}
