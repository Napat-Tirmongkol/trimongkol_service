"use client";

import Link from "next/link";
import { useLanguage } from "@/components/LanguageProvider";

export default function AboutPage() {
  const { t } = useLanguage();

  const values = [
    { title: t.about.value1Title, desc: t.about.value1Desc },
    { title: t.about.value2Title, desc: t.about.value2Desc },
    { title: t.about.value3Title, desc: t.about.value3Desc },
    { title: t.about.value4Title, desc: t.about.value4Desc },
  ];

  return (
    <>
      <section className="relative overflow-hidden border-b border-slate-200 bg-slate-50">
        <div className="absolute inset-0 bg-grid opacity-30" aria-hidden="true" />
        <div className="relative mx-auto max-w-4xl px-6 py-20 text-center md:py-24">
          <h1 className="text-4xl font-bold tracking-tight text-slate-900 md:text-5xl">
            {t.about.heading}
          </h1>
          <p className="mx-auto mt-4 max-w-2xl text-lg text-slate-600">
            {t.about.subheading}
          </p>
        </div>
      </section>

      <section className="py-20">
        <div className="mx-auto grid max-w-6xl gap-12 px-6 md:grid-cols-2 md:items-center">
          <div>
            <span className="text-sm font-semibold uppercase tracking-wider text-brand-700">
              {t.about.story}
            </span>
            <h2 className="mt-3 text-3xl font-bold tracking-tight text-slate-900">
              {t.brand.name}
            </h2>
            <p className="mt-6 text-base leading-relaxed text-slate-600">
              {t.about.storyText}
            </p>
          </div>
          <div className="relative">
            <div className="absolute -inset-4 rounded-3xl bg-gradient-to-br from-brand-100 to-brand-300 opacity-50 blur-2xl" aria-hidden="true" />
            <div className="relative rounded-3xl border border-slate-200 bg-white p-8 shadow-xl">
              <div className="grid grid-cols-2 gap-6">
                <div className="rounded-xl bg-brand-50 p-5">
                  <div className="text-3xl font-bold text-brand-700">50+</div>
                  <div className="mt-1 text-xs text-slate-600">
                    {t.home.statsClients}
                  </div>
                </div>
                <div className="rounded-xl bg-brand-50 p-5">
                  <div className="text-3xl font-bold text-brand-700">120+</div>
                  <div className="mt-1 text-xs text-slate-600">
                    {t.home.statsProjects}
                  </div>
                </div>
                <div className="rounded-xl bg-brand-50 p-5">
                  <div className="text-3xl font-bold text-brand-700">8+</div>
                  <div className="mt-1 text-xs text-slate-600">
                    {t.home.statsExperience}
                  </div>
                </div>
                <div className="rounded-xl bg-brand-50 p-5">
                  <div className="text-3xl font-bold text-brand-700">24/7</div>
                  <div className="mt-1 text-xs text-slate-600">
                    {t.home.statsSupport}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className="border-y border-slate-200 bg-slate-50 py-20">
        <div className="mx-auto grid max-w-6xl gap-8 px-6 md:grid-cols-2">
          <div className="rounded-2xl border border-slate-200 bg-white p-8">
            <div className="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-600 text-white">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <circle cx="12" cy="12" r="10" />
                <polygon points="10 8 16 12 10 16 10 8" />
              </svg>
            </div>
            <h3 className="mt-5 text-xl font-semibold text-slate-900">
              {t.about.missionTitle}
            </h3>
            <p className="mt-3 text-sm leading-relaxed text-slate-600">
              {t.about.missionText}
            </p>
          </div>
          <div className="rounded-2xl border border-slate-200 bg-white p-8">
            <div className="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-600 text-white">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                <circle cx="12" cy="12" r="3" />
              </svg>
            </div>
            <h3 className="mt-5 text-xl font-semibold text-slate-900">
              {t.about.visionTitle}
            </h3>
            <p className="mt-3 text-sm leading-relaxed text-slate-600">
              {t.about.visionText}
            </p>
          </div>
        </div>
      </section>

      <section className="py-20">
        <div className="mx-auto max-w-6xl px-6">
          <h2 className="text-center text-3xl font-bold tracking-tight text-slate-900 md:text-4xl">
            {t.about.valuesHeading}
          </h2>
          <div className="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
            {values.map((value, i) => (
              <div
                key={value.title}
                className="rounded-2xl border border-slate-200 bg-white p-6"
              >
                <span className="text-sm font-semibold text-brand-700">
                  0{i + 1}
                </span>
                <h3 className="mt-3 text-lg font-semibold text-slate-900">
                  {value.title}
                </h3>
                <p className="mt-2 text-sm leading-relaxed text-slate-600">
                  {value.desc}
                </p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="bg-slate-50 py-20">
        <div className="mx-auto max-w-3xl px-6 text-center">
          <h2 className="text-3xl font-bold tracking-tight text-slate-900 md:text-4xl">
            {t.home.ctaHeading}
          </h2>
          <p className="mt-4 text-lg text-slate-600">{t.home.ctaSubheading}</p>
          <Link
            href="/contact"
            className="mt-8 inline-block rounded-md bg-brand-600 px-8 py-3 text-base font-semibold text-white shadow-lg shadow-brand-600/20 transition hover:bg-brand-700"
          >
            {t.home.ctaButton}
          </Link>
        </div>
      </section>
    </>
  );
}
