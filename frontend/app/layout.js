import "./globals.css";

export const metadata = {
  title: "IoT Platform",
  description: "Operational dashboard for multi-tenant IoT monitoring",
};

export default function RootLayout({ children }) {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  );
}
