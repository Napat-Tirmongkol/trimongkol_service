/** @type {import('next').NextConfig} */
const nextConfig = {
  reactStrictMode: true,
  output: "export",
  basePath: "/tirmongkol_service",
  trailingSlash: true,
  images: { unoptimized: true },
};

module.exports = nextConfig;
