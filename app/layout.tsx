import type { Metadata } from "next";
import "./globals.css";
import { LanguageProvider } from "@/components/LanguageProvider";
import { Navbar } from "@/components/Navbar";
import { Footer } from "@/components/Footer";
import { site } from "@/lib/site";

export const metadata: Metadata = {
  metadataBase: new URL(site.url),
  title: {
    default: `${site.name} — End-to-end Business Software`,
    template: `%s — ${site.name}`,
  },
  description:
    "We build end-to-end business management systems — queue booking, product management, POS, CRM, websites, and HR — tailored to Thai SMEs.",
  keywords: [
    "queue booking system",
    "product management",
    "POS system",
    "ระบบจองคิว",
    "ระบบจัดการสินค้า",
    "ระบบ POS",
    "Tirmongkol",
    "SME software Thailand",
  ],
  authors: [{ name: site.name }],
  openGraph: {
    type: "website",
    url: site.url,
    title: `${site.name} — End-to-end Business Software`,
    description:
      "End-to-end business management systems for Thai SMEs — queue booking, product management, POS, CRM, websites, and HR.",
    siteName: site.name,
  },
  twitter: {
    card: "summary_large_image",
    title: `${site.name} — End-to-end Business Software`,
    description:
      "End-to-end business management systems for Thai SMEs — queue booking, product management, POS, CRM, websites, and HR.",
  },
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="th">
      <body className="flex min-h-screen flex-col bg-white text-slate-900">
        <LanguageProvider>
          <Navbar />
          <main className="flex-1">{children}</main>
          <Footer />
        </LanguageProvider>
      </body>
    </html>
  );
}
