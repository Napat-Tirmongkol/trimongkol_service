"use client";

import Link from "next/link";
import { useLanguage } from "@/components/LanguageProvider";

export default function HomePage() {
  const { t } = useLanguage();

  const stats = [
    { value: "50+", label: t.home.statsClients },
    { value: "120+", label: t.home.statsProjects },
    { value: "8+", label: t.home.statsExperience },
    { value: "24/7", label: t.home.statsSupport },
  ];

  const whyItems = [
    {
      title: t.home.why1Title,
      desc: t.home.why1Desc,
      icon: (
        <path d="M12 6V3M6 12H3M12 18v3M18 12h3M7.05 7.05L4.93 4.93M16.95 7.05l2.12-2.12M16.95 16.95l2.12 2.12M7.05 16.95l-2.12 2.12" />
      ),
    },
    {
      title: t.home.why2Title,
      desc: t.home.why2Desc,
      icon: <path d="M9 12l2 2 4-4M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />,
    },
    {
      title: t.home.why3Title,
      desc: t.home.why3Desc,
      icon: (
        <path d="M18 8a6 6 0 11-12 0 6 6 0 0112 0zM12 14v7M9 21h6" />
      ),
    },
    {
      title: t.home.why4Title,
      desc: t.home.why4Desc,
      icon: (
        <path d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3 1.343 3 3-1.343 3-3 3m0-12V5m0 14v2M5 12H3m18 0h-2" />
      ),
    },
  ];

  return (
    <>
      <section className="relative overflow-hidden">
        <div className="absolute inset-0 bg-grid opacity-40" aria-hidden="true" />
        <div className="absolute -top-32 right-1/3 h-96 w-96 rounded-full bg-brand-200 blur-3xl opacity-50" aria-hidden="true" />
        <div className="absolute top-40 -left-20 h-72 w-72 rounded-full bg-brand-300 blur-3xl opacity-40" aria-hidden="true" />

        <div className="relative mx-auto max-w-7xl px-6 pt-20 pb-24 md:pt-28 md:pb-32">
          <div className="mx-auto max-w-3xl text-center animate-fade-in-up">
            <span className="inline-block rounded-full border border-brand-200 bg-brand-50 px-4 py-1 text-xs font-medium uppercase tracking-wider text-brand-700">
              {t.home.heroEyebrow}
            </span>
            <h1 className="mt-6 text-4xl font-bold leading-tight tracking-tight text-slate-900 md:text-6xl">
              {t.home.heroTitle.split(" ").map((word, i, arr) => (
                <span key={i}>
                  {i === arr.length - 1 ? (
                    <span className="text-gradient">{word}</span>
                  ) : (
                    word
                  )}
                  {i < arr.length - 1 && " "}
                </span>
              ))}
            </h1>
            <p className="mt-6 text-lg text-slate-600 md:text-xl">
              {t.home.heroDescription}
            </p>
            <div className="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
              <Link
                href="/services"
                className="w-full rounded-md bg-brand-600 px-6 py-3 text-base font-medium text-white shadow-lg shadow-brand-600/20 transition hover:bg-brand-700 sm:w-auto"
              >
                {t.home.heroPrimary}
              </Link>
              <Link
                href="/contact"
                className="w-full rounded-md border border-slate-300 bg-white px-6 py-3 text-base font-medium text-slate-700 transition hover:border-slate-400 hover:bg-slate-50 sm:w-auto"
              >
                {t.home.heroSecondary}
              </Link>
            </div>
          </div>
        </div>
      </section>

      <section className="border-y border-slate-200 bg-slate-50">
        <div className="mx-auto max-w-7xl px-6 py-12">
          <dl className="grid grid-cols-2 gap-8 md:grid-cols-4">
            {stats.map((stat) => (
              <div key={stat.label} className="text-center">
                <dt className="text-3xl font-bold text-slate-900 md:text-4xl">
                  {stat.value}
                </dt>
                <dd className="mt-2 text-sm text-slate-600">{stat.label}</dd>
              </div>
            ))}
          </dl>
        </div>
      </section>

      <section className="py-20 md:py-28">
        <div className="mx-auto max-w-7xl px-6">
          <div className="mx-auto max-w-2xl text-center">
            <h2 className="text-3xl font-bold tracking-tight text-slate-900 md:text-4xl">
              {t.home.servicesHeading}
            </h2>
            <p className="mt-4 text-lg text-slate-600">
              {t.home.servicesSubheading}
            </p>
          </div>

          <div className="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            {t.services.items.slice(0, 6).map((service, i) => (
              <div
                key={service.title}
                className="group rounded-2xl border border-slate-200 bg-white p-6 transition hover:border-brand-300 hover:shadow-lg hover:shadow-brand-100/40"
              >
                <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-600 group-hover:bg-brand-600 group-hover:text-white transition">
                  <span className="text-lg font-bold">{i + 1}</span>
                </div>
                <h3 className="mt-5 text-lg font-semibold text-slate-900">
                  {service.title}
                </h3>
                <p className="mt-2 text-sm leading-relaxed text-slate-600">
                  {service.description}
                </p>
              </div>
            ))}
          </div>

          <div className="mt-10 text-center">
            <Link
              href="/services"
              className="inline-flex items-center gap-2 text-sm font-medium text-brand-700 hover:text-brand-800"
            >
              {t.home.heroPrimary}
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <line x1="5" y1="12" x2="19" y2="12" />
                <polyline points="12 5 19 12 12 19" />
              </svg>
            </Link>
          </div>
        </div>
      </section>

      <section className="bg-slate-50 py-20 md:py-28">
        <div className="mx-auto max-w-7xl px-6">
          <div className="mx-auto max-w-2xl text-center">
            <h2 className="text-3xl font-bold tracking-tight text-slate-900 md:text-4xl">
              {t.home.whyHeading}
            </h2>
            <p className="mt-4 text-lg text-slate-600">
              {t.home.whySubheading}
            </p>
          </div>

          <div className="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
            {whyItems.map((item) => (
              <div
                key={item.title}
                className="rounded-2xl border border-slate-200 bg-white p-6"
              >
                <div className="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-600 text-white">
                  <svg
                    width="22"
                    height="22"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                  >
                    {item.icon}
                  </svg>
                </div>
                <h3 className="mt-5 text-base font-semibold text-slate-900">
                  {item.title}
                </h3>
                <p className="mt-2 text-sm leading-relaxed text-slate-600">
                  {item.desc}
                </p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="py-20 md:py-28">
        <div className="mx-auto max-w-5xl px-6">
          <div className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 px-8 py-16 text-center text-white shadow-2xl shadow-brand-900/20 md:px-16">
            <div className="absolute inset-0 bg-grid opacity-10" aria-hidden="true" />
            <div className="relative">
              <h2 className="text-3xl font-bold tracking-tight md:text-4xl">
                {t.home.ctaHeading}
              </h2>
              <p className="mt-4 text-lg text-brand-100">
                {t.home.ctaSubheading}
              </p>
              <Link
                href="/contact"
                className="mt-8 inline-block rounded-md bg-white px-8 py-3 text-base font-semibold text-brand-700 shadow-lg transition hover:bg-brand-50"
              >
                {t.home.ctaButton}
              </Link>
            </div>
          </div>
        </div>
      </section>
    </>
  );
}
