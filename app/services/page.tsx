"use client";

import Link from "next/link";
import { useLanguage } from "@/components/LanguageProvider";

export default function ServicesPage() {
  const { t } = useLanguage();

  return (
    <>
      <section className="relative overflow-hidden border-b border-slate-200 bg-slate-50">
        <div className="absolute inset-0 bg-grid opacity-30" aria-hidden="true" />
        <div className="relative mx-auto max-w-4xl px-6 py-20 text-center md:py-24">
          <h1 className="text-4xl font-bold tracking-tight text-slate-900 md:text-5xl">
            {t.services.heading}
          </h1>
          <p className="mx-auto mt-4 max-w-2xl text-lg text-slate-600">
            {t.services.subheading}
          </p>
        </div>
      </section>

      <section className="py-20">
        <div className="mx-auto max-w-7xl px-6">
          <div className="grid gap-8 md:grid-cols-2">
            {t.services.items.map((service, i) => (
              <div
                key={service.title}
                className="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-8 transition hover:border-brand-300 hover:shadow-xl hover:shadow-brand-100/40"
              >
                <div className="absolute right-0 top-0 -mt-12 -mr-12 h-32 w-32 rounded-full bg-brand-100 opacity-0 transition group-hover:opacity-60" aria-hidden="true" />
                <div className="relative">
                  <div className="flex items-center gap-3">
                    <span className="grid h-10 w-10 place-items-center rounded-lg bg-brand-600 font-bold text-white">
                      {String(i + 1).padStart(2, "0")}
                    </span>
                    <h2 className="text-xl font-semibold text-slate-900">
                      {service.title}
                    </h2>
                  </div>
                  <p className="mt-4 text-sm leading-relaxed text-slate-600">
                    {service.description}
                  </p>
                  <ul className="mt-6 grid gap-2 sm:grid-cols-2">
                    {service.features.map((feature) => (
                      <li
                        key={feature}
                        className="flex items-start gap-2 text-sm text-slate-700"
                      >
                        <svg
                          className="mt-0.5 h-4 w-4 flex-shrink-0 text-brand-600"
                          viewBox="0 0 24 24"
                          fill="none"
                          stroke="currentColor"
                          strokeWidth="2.5"
                          strokeLinecap="round"
                          strokeLinejoin="round"
                        >
                          <polyline points="20 6 9 17 4 12" />
                        </svg>
                        {feature}
                      </li>
                    ))}
                  </ul>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="border-y border-slate-200 bg-slate-50 py-20">
        <div className="mx-auto max-w-5xl px-6">
          <h2 className="text-center text-3xl font-bold tracking-tight text-slate-900 md:text-4xl">
            {t.services.includedHeading}
          </h2>
          <ul className="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {t.services.included.map((item) => (
              <li
                key={item}
                className="flex items-start gap-3 rounded-xl border border-slate-200 bg-white p-4"
              >
                <span className="grid h-8 w-8 flex-shrink-0 place-items-center rounded-lg bg-brand-100 text-brand-700">
                  <svg
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2.5"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                  >
                    <polyline points="20 6 9 17 4 12" />
                  </svg>
                </span>
                <span className="text-sm text-slate-700">{item}</span>
              </li>
            ))}
          </ul>
        </div>
      </section>

      <section className="py-20">
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
