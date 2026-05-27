"use client";

import Link from "next/link";
import { useLanguage } from "@/components/LanguageProvider";
import { site } from "@/lib/site";

export function Footer() {
  const { t } = useLanguage();
  const year = new Date().getFullYear();

  const links = [
    { href: "/", label: t.nav.home },
    { href: "/services", label: t.nav.services },
    { href: "/about", label: t.nav.about },
    { href: "/contact", label: t.nav.contact },
  ];

  return (
    <footer className="border-t border-slate-200 bg-slate-50">
      <div className="mx-auto max-w-7xl px-6 py-12">
        <div className="grid gap-10 md:grid-cols-3">
          <div>
            <div className="flex items-center gap-2">
              <span className="grid h-9 w-9 place-items-center rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 text-white font-bold">
                T
              </span>
              <span className="text-lg font-semibold text-slate-900">
                {t.brand.name}
              </span>
            </div>
            <p className="mt-3 max-w-sm text-sm text-slate-600">
              {t.footer.tagline}
            </p>
          </div>

          <div>
            <h3 className="text-sm font-semibold uppercase tracking-wider text-slate-900">
              {t.footer.navHeading}
            </h3>
            <ul className="mt-4 space-y-2">
              {links.map((link) => (
                <li key={link.href}>
                  <Link
                    href={link.href}
                    className="text-sm text-slate-600 hover:text-brand-700"
                  >
                    {link.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          <div>
            <h3 className="text-sm font-semibold uppercase tracking-wider text-slate-900">
              {t.footer.contactHeading}
            </h3>
            <ul className="mt-4 space-y-2 text-sm text-slate-600">
              <li>
                <a
                  href={`mailto:${site.email}`}
                  className="hover:text-brand-700"
                >
                  {site.email}
                </a>
              </li>
              <li>{site.phone}</li>
              <li>LINE: {site.line}</li>
            </ul>
          </div>
        </div>

        <div className="mt-10 border-t border-slate-200 pt-6 text-center text-xs text-slate-500">
          &copy; {year} {t.brand.name}. {t.footer.copyright}.
        </div>
      </div>
    </footer>
  );
}
