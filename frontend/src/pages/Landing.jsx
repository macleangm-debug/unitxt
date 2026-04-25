import { Link } from "react-router-dom";
import { ArrowUpRight, Globe2, Zap, ShieldCheck, Layers, MessageSquare, BarChart3 } from "lucide-react";

const FEATURES = [
  {
    icon: Globe2,
    title: "Reach 12 countries today",
    body: "Direct local routes plus global fallbacks deliver to every operator we cover — no patchy networks, no surprise blocks.",
  },
  {
    icon: Zap,
    title: "SMS + WhatsApp · one queue",
    body: "Quick send, bulk upload, scheduling, merge tags, recurring campaigns. Hit 100k recipients in under a minute.",
  },
  {
    icon: Layers,
    title: "Reseller-ready",
    body: "Run a white-label messaging business: float wallets, fund clients, set your own retail prices, earn margin or commission — all baked in.",
  },
  {
    icon: ShieldCheck,
    title: "Sender IDs that actually get approved",
    body: "Country-aware applications, document checklist, provider compatibility. We track the lifecycle so you don't lose another campaign to compliance.",
  },
  {
    icon: MessageSquare,
    title: "Plug into your stack",
    body: "REST API, webhooks for delivery reports, ready-made adapters for banks, mobile-money and CRMs. Go live this week, not next quarter.",
  },
  {
    icon: BarChart3,
    title: "See every credit, every message",
    body: "Live delivery dashboards with failure-reason breakdowns, per-campaign P&L, and audit logs your finance team can sign off on.",
  },
];

export default function Landing() {
  return (
    <div className="min-h-screen bg-[#0A0A0A] text-white">
      {/* NAV */}
      <nav className="sticky top-0 z-40 border-b border-zinc-900 bg-[#0A0A0A]/80 backdrop-blur-xl">
        <div className="mx-auto flex h-14 max-w-7xl items-center justify-between px-6">
          <Link to="/" className="flex items-center gap-2" data-testid="brand-link">
            <div className="grid h-7 w-7 place-items-center border border-white/30 bg-white text-black font-bold">
              <span className="font-display text-sm">u</span>
            </div>
            <span className="font-display text-lg font-bold tracking-tight">unitxt</span>
            <span className="ml-2 hidden font-mono text-[10px] tracking-widest text-zinc-500 sm:inline">
              SMS · WHATSAPP · 12 COUNTRIES
            </span>
          </Link>
          <div className="flex items-center gap-6 text-sm">
            <a href="#platform" className="hidden text-zinc-400 transition hover:text-white sm:inline">Platform</a>
            <a href="#how" className="hidden text-zinc-400 transition hover:text-white sm:inline">How it works</a>
            <Link to="/login" className="text-zinc-300 transition hover:text-white" data-testid="nav-login">
              Sign in
            </Link>
            <Link
              to="/register"
              data-testid="nav-register"
              className="inline-flex items-center gap-1 rounded-sm bg-white px-3 py-1.5 text-sm font-medium text-black transition hover:bg-zinc-200"
            >
              Get started <ArrowUpRight className="h-3.5 w-3.5" />
            </Link>
          </div>
        </div>
      </nav>

      {/* HERO */}
      <section className="relative overflow-hidden">
        <div className="absolute inset-0 grid-pattern opacity-60" />
        <div
          className="absolute inset-0"
          style={{
            backgroundImage:
              "url(https://images.unsplash.com/photo-1633098096956-afdc8bcc8552?auto=format&fit=crop&w=1920&q=70)",
            backgroundSize: "cover",
            backgroundPosition: "center",
            opacity: 0.08,
          }}
        />
        <div className="absolute inset-0 bg-gradient-to-b from-transparent via-[#0A0A0A]/40 to-[#0A0A0A]" />
        <div className="relative mx-auto max-w-7xl px-6 pb-32 pt-24 sm:pt-32">
          <div className="rise inline-flex items-center gap-2 border border-zinc-800 bg-zinc-950/60 px-3 py-1 font-mono text-[10px] uppercase tracking-[0.25em] text-zinc-400">
            <span className="signal-dot" style={{ color: "#34C759" }} />
            v1.0 · LIVE IN 12 COUNTRIES
          </div>
          <h1 className="rise rise-1 mt-8 font-display text-5xl font-bold leading-[0.95] tracking-tighter sm:text-7xl md:text-8xl">
            Reach every customer.<br />
            <span className="text-zinc-500">In every country.</span>
          </h1>
          <p className="rise rise-2 mt-8 max-w-2xl text-lg leading-relaxed text-zinc-400">
            unitxt is the fastest way to send SMS and WhatsApp at scale. Local routes in
            12 countries, 98.7% delivery, transparent per-message pricing — and a free
            workspace you can launch a campaign from in 60 seconds.
          </p>
          <div className="rise rise-3 mt-12 flex flex-col gap-3 sm:flex-row">
            <Link
              to="/register"
              data-testid="hero-cta-register"
              className="inline-flex items-center justify-center gap-2 rounded-sm bg-white px-6 py-3 font-medium text-black transition hover:bg-zinc-200"
            >
              Open a free workspace <ArrowUpRight className="h-4 w-4" />
            </Link>
            <Link
              to="/login"
              data-testid="hero-cta-login"
              className="inline-flex items-center justify-center gap-2 rounded-sm border border-zinc-800 bg-zinc-950 px-6 py-3 font-medium text-white transition hover:bg-zinc-900"
            >
              Sign in to dashboard
            </Link>
          </div>

          {/* Stat ticker */}
          <div className="rise rise-4 mt-20 grid grid-cols-2 gap-px overflow-hidden rounded-sm border border-zinc-900 bg-zinc-900 sm:grid-cols-4">
            {[
              ["2.4B", "Messages routed / mo"],
              ["12", "Countries online"],
              ["98.7%", "Avg delivery rate"],
              ["120ms", "Median dispatch"],
            ].map(([v, l]) => (
              <div key={l} className="bg-[#0A0A0A] px-6 py-6">
                <div className="font-mono text-3xl font-medium tracking-tight">{v}</div>
                <div className="label-overline mt-1">{l}</div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* PORTAL CARDS */}
      <section id="platform" className="border-t border-zinc-900 bg-[#0A0A0A]">
        <div className="mx-auto max-w-7xl px-6 py-24">
          <div className="label-overline">For the people who actually send</div>
          <h2 className="mt-4 max-w-3xl font-display text-4xl font-semibold tracking-tight sm:text-5xl">
            Built for the businesses you run, not the boxes you tick.
          </h2>
          <div className="mt-12 grid gap-px overflow-hidden border border-zinc-900 bg-zinc-900 md:grid-cols-3">
            {[
              {
                tag: "FOR BRANDS",
                title: "Reach customers in seconds.",
                points: ["Bulk SMS to 100k+", "WhatsApp campaigns", "Personalised merge tags", "Live delivery reports"],
              },
              {
                tag: "FOR RESELLERS",
                title: "Launch a messaging business.",
                points: ["Brand it as your own", "Onboard & fund clients", "Set your retail pricing", "Earn margin every send"],
              },
              {
                tag: "FOR ENTERPRISES",
                title: "Scale without the headaches.",
                points: ["Local routes, global fallback", "Per-team wallets", "API + webhooks", "SLA-backed support"],
              },
            ].map((c, i) => (
              <div key={c.tag} className="bg-[#0A0A0A] p-8">
                <div className="font-mono text-[10px] tracking-[0.25em] text-zinc-500">{c.tag}</div>
                <h3 className="mt-4 font-display text-2xl font-semibold tracking-tight">{c.title}</h3>
                <ul className="mt-6 space-y-2 text-sm text-zinc-400">
                  {c.points.map((p) => (
                    <li key={p} className="flex items-center gap-2">
                      <span className="h-1 w-3 bg-white/40" /> {p}
                    </li>
                  ))}
                </ul>
                <div className="mt-8 font-mono text-[10px] tracking-widest text-zinc-600">
                  PORTAL · 0{i + 1}
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* FEATURES */}
      <section id="how" className="border-t border-zinc-900 bg-[#0A0A0A]">
        <div className="mx-auto max-w-7xl px-6 py-24">
          <div className="grid gap-16 lg:grid-cols-[1fr,1.4fr]">
            <div>
              <div className="label-overline">What you get</div>
              <h2 className="mt-4 font-display text-4xl font-semibold leading-tight tracking-tight">
                Send more. Lose less. Sleep better.
              </h2>
              <p className="mt-6 max-w-md text-zinc-400">
                Pre-flight validation catches bad numbers before you spend a credit. Smart
                routing picks the best operator route for every message. Plain-English
                delivery reports tell you exactly where things went right — and what to
                fix when they don't.
              </p>
            </div>
            <div className="grid gap-px overflow-hidden border border-zinc-900 bg-zinc-900 sm:grid-cols-2">
              {FEATURES.map((f) => (
                <div key={f.title} className="bg-[#0A0A0A] p-6">
                  <f.icon className="h-5 w-5 text-zinc-300" strokeWidth={1.5} />
                  <h3 className="mt-4 font-display text-lg font-semibold tracking-tight">{f.title}</h3>
                  <p className="mt-2 text-sm leading-relaxed text-zinc-500">{f.body}</p>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="border-t border-zinc-900">
        <div className="mx-auto max-w-7xl px-6 py-24">
          <div className="flex flex-col items-start justify-between gap-6 border border-zinc-800 bg-gradient-to-br from-zinc-950 to-[#0A0A0A] p-10 lg:flex-row lg:items-center">
            <div>
              <div className="label-overline">Ready when you are</div>
              <h3 className="mt-3 font-display text-3xl font-semibold tracking-tight sm:text-4xl">
                Start sending in 60 seconds.
              </h3>
              <p className="mt-2 text-zinc-400">No credit card. Demo wallet. Mock provider live by default.</p>
            </div>
            <div className="flex flex-wrap gap-3">
              <Link
                to="/register"
                data-testid="cta-register"
                className="inline-flex items-center gap-2 rounded-sm bg-white px-5 py-2.5 font-medium text-black hover:bg-zinc-200"
              >
                Create workspace <ArrowUpRight className="h-4 w-4" />
              </Link>
              <Link
                to="/login"
                data-testid="cta-login"
                className="inline-flex items-center gap-2 rounded-sm border border-zinc-800 bg-zinc-950 px-5 py-2.5 font-medium hover:bg-zinc-900"
              >
                Sign in
              </Link>
            </div>
          </div>
        </div>
      </section>

      <footer className="border-t border-zinc-900 py-10">
        <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-6 text-xs text-zinc-600 sm:flex-row">
          <div className="font-mono tracking-widest">© 2026 UNITXT · SMS & WHATSAPP, GLOBAL</div>
          <div className="flex gap-4">
            <span>Privacy</span>
            <span>Compliance</span>
            <span>Status</span>
          </div>
        </div>
      </footer>
    </div>
  );
}
