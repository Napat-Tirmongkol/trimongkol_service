"use client";

import { useState } from "react";
import { useLanguage } from "@/components/LanguageProvider";
import { site } from "@/lib/site";

type FormStatus = "idle" | "sending" | "success";

export default function ContactPage() {
  const { t } = useLanguage();
  const [status, setStatus] = useState<FormStatus>("idle");

  const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setStatus("sending");
    const form = e.currentTarget;
    const data = new FormData(form);
    const subject = encodeURIComponent(
      `[${site.name}] ${data.get("name") ?? ""}`,
    );
    const body = encodeURIComponent(
      `Name: ${data.get("name") ?? ""}\n` +
        `Email: ${data.get("email") ?? ""}\n` +
        `Phone: ${data.get("phone") ?? ""}\n` +
        `Company: ${data.get("company") ?? ""}\n\n` +
        `${data.get("message") ?? ""}`,
    );
    window.location.href = `mailto:${site.email}?subject=${subject}&body=${body}`;
    setTimeout(() => {
      setStatus("success");
      form.reset();
    }, 600);
  };

  return (
    <>
      <section className="relative overflow-hidden border-b border-slate-200 bg-slate-50">
        <div className="absolute inset-0 bg-grid opacity-30" aria-hidden="true" />
        <div className="relative mx-auto max-w-4xl px-6 py-20 text-center md:py-24">
          <h1 className="text-4xl font-bold tracking-tight text-slate-900 md:text-5xl">
            {t.contact.heading}
          </h1>
          <p className="mx-auto mt-4 max-w-2xl text-lg text-slate-600">
            {t.contact.subheading}
          </p>
        </div>
      </section>

      <section className="py-20">
        <div className="mx-auto grid max-w-6xl gap-10 px-6 md:grid-cols-5">
          <div className="md:col-span-2">
            <h2 className="text-xl font-semibold text-slate-900">
              {t.contact.infoTitle}
            </h2>
            <ul className="mt-6 space-y-5">
              <li className="flex gap-4">
                <span className="grid h-10 w-10 flex-shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-700">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                    <polyline points="22,6 12,13 2,6" />
                  </svg>
                </span>
                <div>
                  <div className="text-xs font-medium uppercase tracking-wider text-slate-500">
                    {t.contact.infoEmail}
                  </div>
                  <a
                    href={`mailto:${site.email}`}
                    className="mt-0.5 block text-sm font-medium text-slate-900 hover:text-brand-700"
                  >
                    {site.email}
                  </a>
                </div>
              </li>

              <li className="flex gap-4">
                <span className="grid h-10 w-10 flex-shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-700">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.37 1.9.72 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.35 1.85.59 2.81.72A2 2 0 0 1 22 16.92z" />
                  </svg>
                </span>
                <div>
                  <div className="text-xs font-medium uppercase tracking-wider text-slate-500">
                    {t.contact.infoPhone}
                  </div>
                  <div className="mt-0.5 text-sm font-medium text-slate-900">
                    {site.phone}
                  </div>
                </div>
              </li>

              <li className="flex gap-4">
                <span className="grid h-10 w-10 flex-shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-700">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" />
                  </svg>
                </span>
                <div>
                  <div className="text-xs font-medium uppercase tracking-wider text-slate-500">
                    {t.contact.infoLine}
                  </div>
                  <div className="mt-0.5 text-sm font-medium text-slate-900">
                    {site.line}
                  </div>
                </div>
              </li>

              <li className="flex gap-4">
                <span className="grid h-10 w-10 flex-shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-700">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <circle cx="12" cy="12" r="10" />
                    <polyline points="12 6 12 12 16 14" />
                  </svg>
                </span>
                <div>
                  <div className="text-xs font-medium uppercase tracking-wider text-slate-500">
                    {t.contact.infoHoursTitle}
                  </div>
                  <div className="mt-0.5 text-sm font-medium text-slate-900">
                    {t.contact.infoHours}
                  </div>
                </div>
              </li>
            </ul>
          </div>

          <form
            onSubmit={handleSubmit}
            className="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm md:col-span-3"
          >
            <div className="grid gap-5 sm:grid-cols-2">
              <Field
                label={t.contact.formName}
                name="name"
                required
                autoComplete="name"
              />
              <Field
                label={t.contact.formEmail}
                name="email"
                type="email"
                required
                autoComplete="email"
              />
              <Field
                label={t.contact.formPhone}
                name="phone"
                type="tel"
                autoComplete="tel"
              />
              <Field
                label={t.contact.formCompany}
                name="company"
                autoComplete="organization"
              />
            </div>

            <div className="mt-5">
              <label
                htmlFor="message"
                className="block text-sm font-medium text-slate-700"
              >
                {t.contact.formMessage}
              </label>
              <textarea
                id="message"
                name="message"
                rows={5}
                required
                placeholder={t.contact.formMessagePlaceholder}
                className="mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20"
              />
            </div>

            <button
              type="submit"
              disabled={status === "sending"}
              className="mt-6 w-full rounded-md bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 transition hover:bg-brand-700 disabled:opacity-60"
            >
              {status === "sending" ? t.contact.formSending : t.contact.formSubmit}
            </button>

            {status === "success" && (
              <p className="mt-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {t.contact.formSuccess}
              </p>
            )}
          </form>
        </div>
      </section>
    </>
  );
}

function Field({
  label,
  name,
  type = "text",
  required,
  autoComplete,
}: {
  label: string;
  name: string;
  type?: string;
  required?: boolean;
  autoComplete?: string;
}) {
  return (
    <div>
      <label
        htmlFor={name}
        className="block text-sm font-medium text-slate-700"
      >
        {label}
        {required && <span className="ml-1 text-rose-500">*</span>}
      </label>
      <input
        id={name}
        name={name}
        type={type}
        required={required}
        autoComplete={autoComplete}
        className="mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20"
      />
    </div>
  );
}
