"use client";

import { useLanguage } from "@/components/LanguageProvider";

export function LanguageToggle() {
  const { locale, setLocale } = useLanguage();

  return (
    <div
      role="group"
      aria-label="Language"
      className="inline-flex items-center rounded-md border border-slate-200 bg-white p-0.5 text-xs font-medium"
    >
      <button
        type="button"
        aria-pressed={locale === "th"}
        onClick={() => setLocale("th")}
        className={`rounded px-2.5 py-1 transition ${
          locale === "th"
            ? "bg-brand-600 text-white"
            : "text-slate-600 hover:text-slate-900"
        }`}
      >
        TH
      </button>
      <button
        type="button"
        aria-pressed={locale === "en"}
        onClick={() => setLocale("en")}
        className={`rounded px-2.5 py-1 transition ${
          locale === "en"
            ? "bg-brand-600 text-white"
            : "text-slate-600 hover:text-slate-900"
        }`}
      >
        EN
      </button>
    </div>
  );
}
