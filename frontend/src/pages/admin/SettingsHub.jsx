import { useEffect, useState, useMemo } from "react";
import { toast } from "sonner";
import http, { fmtErr, humanize } from "@/lib/api";
import { PageHeader, Card, Field, Input, Select, TextArea, Btn, Pill } from "@/components/UI";
import {
  Settings as SettingsIcon, Globe2, Network, DollarSign, Wallet as WalletIcon,
  IdCard, Building2, Bell, ShieldCheck, Megaphone, Save, ChevronRight, Search,
  Sparkles, Layers, Gauge, Users, Percent, Clock, Rocket, ExternalLink, Landmark,
} from "lucide-react";

// ---------- Category definitions ----------
const CATEGORIES = {
  platform:      { label: "Platform",        icon: SettingsIcon, desc: "Brand, currency, timezone, maintenance." },
  credits:       { label: "Credits",         icon: DollarSign,   desc: "Rates per country, WhatsApp, sender ID, unicode." },
  pricing_cfg:   { label: "Reseller margin", icon: Percent,      desc: "Default commission paid out of platform margin." },
  referrals:     { label: "Referrals",       icon: Users,        desc: "Loss-proof referral rewards from pack revenue." },
  streaks:       { label: "Streaks",         icon: Sparkles,     desc: "Gamified bonus at 7 / 30 / 90 days." },
  inactivity:    { label: "Inactivity",      icon: Clock,        desc: "Warn, suspend, recovery cost." },
  queue:         { label: "Queue engine",    icon: Gauge,        desc: "Concurrency, batch size, retries." },
  onboarding:    { label: "Onboarding",      icon: Rocket,       desc: "Signup flow, KYC requirements." },
  compliance:    { label: "Compliance",      icon: ShieldCheck,  desc: "Spam, KYC, daily limits, retention." },
  notifications: { label: "Notifications",   icon: Bell,         desc: "Triggers, thresholds, templates." },
  // module links
  providers:     { label: "Providers",       icon: Network,      desc: "Adapter configuration & failover.",         linkTo: "/admin/providers" },
  countries:     { label: "Countries",       icon: Globe2,       desc: "Geographies and dial codes.",                linkTo: "/admin/countries" },
  pricing:       { label: "Pricing",         icon: DollarSign,   desc: "Per-country provider rates.",                linkTo: "/admin/pricing" },
  wallets:       { label: "Wallets",         icon: WalletIcon,   desc: "All accounts.",                              linkTo: "/admin/wallets" },
  sender_ids:    { label: "Sender IDs",      icon: IdCard,       desc: "Approval queue & policies.",                 linkTo: "/admin/sender-ids" },
  institutions:  { label: "Institutions",    icon: Building2,    desc: "Banks, fintechs, mobile money.",             linkTo: "/admin/institutions" },
  promotions:    { label: "Promotions",      icon: Megaphone,    desc: "Bonus credits & promo codes.",               linkTo: "/admin/promotions" },
  resellers:     { label: "Resellers",       icon: Users,        desc: "Reseller catalog, commission & audit.",     linkTo: "/admin/resellers" },
  reseller_policy: { label: "Reseller policy", icon: ShieldCheck, desc: "Signup, KYC, commission defaults, limits." },
  country_hub:   { label: "Country hub",     icon: Globe2,       desc: "Every country we operate in — pricing, routes, operators.", linkTo: "/admin/country-hub" },
  integrations:  { label: "Integration health", icon: Network,   desc: "Every partner × country with live signal.", linkTo: "/admin/integrations" },
  routing:       { label: "Routing engine",  icon: Network,      desc: "Per-country priority & failover rules.",     linkTo: "/admin/routing" },
  prefixes:      { label: "Mobile prefixes", icon: SettingsIcon, desc: "Map phone prefixes to operators.",           linkTo: "/admin/prefixes" },
  credit_packs:  { label: "Credit packs",    icon: DollarSign,   desc: "Global & country-specific packages.",        linkTo: "/admin/credit-packs" },
  banks:         { label: "Bank accounts",   icon: Landmark,     desc: "Receiving bank details clients transfer credit top-ups into.", linkTo: "/admin/banks" },
  affiliate:     { label: "Affiliate program",   icon: Users,    desc: "Promo-code-driven commissions: model, rate, payouts.",         linkTo: "/admin/affiliate" },
  country_pnl:   { label: "Country P&L",      icon: DollarSign,  desc: "Per-country profit dashboard with VAT-aware true cost.",        linkTo: "/admin/country-pnl" },
  affiliate_old: { label: "Legacy referrals", icon: Users,        desc: "Loss-proof referral rewards (replaced by Affiliate).        Read-only." },
};

// Organize into logical groups
const GROUPS = [
  {
    key: "platform",
    label: "Platform & Onboarding",
    desc: "Brand, signup flow and baseline identity of the system.",
    items: ["platform", "onboarding"],
  },
  {
    key: "economy",
    label: "Economy",
    desc: "Credits, reseller commission, referrals and loyalty rewards.",
    items: ["credits", "pricing_cfg", "credit_packs", "country_pnl", "referrals", "streaks"],
  },
  {
    key: "geographies",
    label: "Geographies",
    desc: "Countries we operate in, partners, routing & prefixes.",
    items: ["country_hub", "integrations", "routing", "providers", "prefixes", "countries", "pricing"],
  },
  {
    key: "messaging",
    label: "Messaging engine",
    desc: "Queue throughput and channel controls.",
    items: ["queue", "sender_ids"],
  },
  {
    key: "governance",
    label: "Governance & lifecycle",
    desc: "Compliance, inactivity handling and notification rules.",
    items: ["compliance", "inactivity", "notifications"],
  },
  {
    key: "distribution",
    label: "Distribution & treasury",
    desc: "Affiliate program, wallets, institutions and promotions.",
    items: ["affiliate", "resellers", "reseller_policy", "wallets", "banks", "institutions", "promotions"],
  },
];

// ---------- Value editor ----------
function renderEditor(setting, onChange) {
  const v = setting.value;
  if (typeof v === "boolean") {
    return (
      <Select value={v ? "1" : "0"} onChange={(e) => onChange(e.target.value === "1")}>
        <option value="1">enabled</option><option value="0">disabled</option>
      </Select>
    );
  }
  if (typeof v === "number") {
    return <Input type="number" value={v} onChange={(e) => onChange(Number(e.target.value))} />;
  }
  if (Array.isArray(v)) {
    return <TextArea value={v.join(", ")} onChange={(e) => onChange(e.target.value.split(",").map(s=>s.trim()).filter(Boolean))} />;
  }
  if (v && typeof v === "object") {
    const str = JSON.stringify(v, null, 2);
    return <TextArea className="min-h-[140px] font-mono text-xs" defaultValue={str} onChange={(e) => {
      try { onChange(JSON.parse(e.target.value)); } catch { /* wait for valid json */ }
    }} />;
  }
  return <Input value={v ?? ""} onChange={(e) => onChange(e.target.value)} />;
}

// ---------- Main component ----------
export default function SettingsHub() {
  const [activeGroup, setActiveGroup] = useState("platform");
  const [active, setActive] = useState("platform");
  const [settings, setSettings] = useState([]);
  const [draft, setDraft] = useState({});
  const [busy, setBusy] = useState(false);
  const [search, setSearch] = useState("");

  const load = () => http.get("/admin/settings").then(r => setSettings(r.data)).catch(()=>{});
  useEffect(() => { load(); }, []);

  const cat = CATEGORIES[active];
  // Settings can live under a category key that isn't in CATEGORIES (e.g. "pricing" setting category).
  // Match by either active key OR, if active is a virtual category, the real backend categories.
  const visible = useMemo(() => {
    let catKeys;
    if (active === "pricing_cfg") catKeys = ["pricing"];
    else if (active === "reseller_policy") catKeys = ["reseller", "pricing"];
    else catKeys = [active];
    let rows = settings.filter(s => catKeys.includes(s.category));
    // For reseller_policy, also include the onboarding.reseller_signup_open flag explicitly
    if (active === "reseller_policy") {
      const extra = settings.filter(s => s.key === "onboarding.reseller_signup_open");
      rows = [...extra, ...rows];
    }
    if (search.trim()) {
      const q = search.toLowerCase();
      rows = rows.filter(s => s.key.toLowerCase().includes(q) ||
                              JSON.stringify(s.value ?? "").toLowerCase().includes(q));
    }
    return rows;
  }, [settings, active, search]);

  const saveOne = async (s) => {
    setBusy(true);
    try {
      const value = (s.key in draft) ? draft[s.key] : s.value;
      await http.put("/admin/settings", { key: s.key, value, category: s.category });
      toast.success(`Saved ${s.key}`);
      const nd = { ...draft }; delete nd[s.key]; setDraft(nd);
      load();
    } catch(err){ toast.error(fmtErr(err.response?.data?.detail)); }
    finally { setBusy(false); }
  };

  // Count settings per category so we can show a badge
  const countFor = (key) => {
    if (key === "pricing_cfg") return settings.filter(s => s.category === "pricing").length;
    if (key === "reseller_policy") {
      return settings.filter(s => s.category === "reseller" || s.category === "pricing"
                                  || s.key === "onboarding.reseller_signup_open").length;
    }
    return settings.filter(s => s.category === key).length;
  };

  const activeGroupDef = GROUPS.find(g => g.key === activeGroup);

  return (
    <div>
      <PageHeader
        overline="Configuration"
        title="Settings hub"
        desc="The brain of unitxt. Almost every behaviour on the platform is driven from here."
        actions={
          <div className="relative">
            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500" strokeWidth={1.5}/>
            <Input
              placeholder="Search keys or values..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-72 pl-9"
              data-testid="settings-search"
            />
          </div>
        }
      />

      {/* GROUP TABS */}
      <div className="mb-4 flex flex-wrap gap-2 border-b border-zinc-900 pb-3" data-testid="settings-groups">
        {GROUPS.map(g => (
          <button
            key={g.key}
            onClick={() => {
              setActiveGroup(g.key);
              const firstInGroup = g.items[0];
              if (firstInGroup) setActive(firstInGroup);
            }}
            data-testid={`settings-group-${g.key}`}
            className={`border px-4 py-2 text-sm transition-all ${
              activeGroup === g.key
                ? "border-white bg-white text-black"
                : "border-zinc-900 bg-[#0e0e0e] text-zinc-400 hover:border-zinc-700 hover:text-white"
            }`}
          >
            {g.label}
          </button>
        ))}
      </div>

      {activeGroupDef && (
        <p className="mb-4 text-sm text-zinc-500">{activeGroupDef.desc}</p>
      )}

      <div className="grid gap-0 border border-zinc-900 lg:grid-cols-[260px,1fr]">
        {/* LEFT RAIL — categories in active group */}
        <div className="border-b border-zinc-900 lg:border-b-0 lg:border-r">
          {activeGroupDef?.items.map(k => {
            const c = CATEGORIES[k];
            if (!c) return null;
            const count = countFor(k);
            return (
              <button
                key={k}
                onClick={() => setActive(k)}
                data-testid={`settings-tab-${k}`}
                className={`group flex w-full items-center gap-3 px-4 py-3 text-left transition-all border-l-2 ${
                  active === k
                    ? "tracing-beam-active text-white"
                    : "border-transparent text-zinc-400 hover:bg-white/5 hover:text-white"
                }`}
              >
                <c.icon className="h-4 w-4 shrink-0" strokeWidth={1.5}/>
                <div className="flex-1 min-w-0">
                  <div className="text-sm font-medium truncate">{c.label}</div>
                  <div className="text-[11px] text-zinc-500 truncate">{c.desc}</div>
                </div>
                {c.linkTo ? (
                  <ExternalLink className="h-3.5 w-3.5 text-zinc-700 group-hover:text-zinc-400 shrink-0"/>
                ) : count > 0 ? (
                  <span className="font-mono text-[10px] text-zinc-600 shrink-0">{count}</span>
                ) : (
                  <ChevronRight className="h-3.5 w-3.5 text-zinc-700 group-hover:text-zinc-400 shrink-0"/>
                )}
              </button>
            );
          })}
        </div>

        {/* RIGHT PANEL */}
        <div className="p-6">
          <div className="flex items-start justify-between border-b border-zinc-900 pb-4">
            <div className="flex items-start gap-3">
              {cat?.icon && (
                <div className="grid h-10 w-10 shrink-0 place-items-center border border-zinc-800 bg-[#141414]">
                  <cat.icon className="h-4 w-4" strokeWidth={1.5}/>
                </div>
              )}
              <div>
                <div className="label-overline">Category</div>
                <h2 className="mt-1 font-display text-2xl font-semibold tracking-tight">{cat?.label}</h2>
                <p className="mt-1 max-w-md text-sm text-zinc-500">{cat?.desc}</p>
              </div>
            </div>
            {cat?.linkTo && (
              <a href={cat.linkTo} className="inline-flex items-center gap-1 text-sm text-white underline-offset-4 hover:underline">
                Open module →
              </a>
            )}
          </div>

          {cat?.linkTo ? (
            <div className="mt-6 grid place-items-center border border-dashed border-zinc-800 p-12 text-center">
              <div className="label-overline">Dedicated module</div>
              <p className="mt-3 max-w-md text-sm text-zinc-400">
                {cat.label} has a full dedicated workspace with rich filters, bulk actions, and forms.
                Open it from the link above.
              </p>
              <a href={cat.linkTo}><Btn className="mt-5"><cat.icon className="h-4 w-4"/>Open {cat.label}</Btn></a>
            </div>
          ) : (
            <div className="mt-6 space-y-3">
              {visible.length === 0 && (
                <div className="border border-dashed border-zinc-800 p-12 text-center text-sm text-zinc-500">
                  {search ? "No settings match your search." : "No keys yet in this category."}
                </div>
              )}
              {visible.map(s => {
                const isDraft = s.key in draft;
                return (
                  <div key={s.key} className="grid gap-3 border border-zinc-900 bg-[#141414] p-4 sm:grid-cols-[1fr,1.5fr,auto] sm:items-end" data-testid={`setting-${s.key}`}>
                    <div>
                      <div className="text-sm font-medium text-white">{humanize(s.key)}</div>
                      <code className="mt-1 block font-mono text-[11px] text-zinc-500 break-all">{s.key}</code>
                    </div>
                    <Field label="Value">
                      {renderEditor({ ...s, value: isDraft ? draft[s.key] : s.value }, (v) => setDraft({ ...draft, [s.key]: v }))}
                    </Field>
                    <Btn onClick={() => saveOne(s)} disabled={busy} className="h-10" data-testid={`save-${s.key}`}>
                      <Save className="h-4 w-4"/>{isDraft ? "Save" : "Saved"}
                    </Btn>
                  </div>
                );
              })}
            </div>
          )}
        </div>
      </div>

      <Card className="mt-6" testid="settings-banner">
        <div className="flex items-center gap-3">
          <Layers className="h-4 w-4 text-zinc-400" strokeWidth={1.5}/>
          <Pill status="info">CONFIGURATION</Pill>
          <div className="text-sm text-zinc-400">
            Add new setting keys from any module. The hub auto-renders editors based on value type
            (boolean, number, list, string, JSON).
          </div>
        </div>
      </Card>
    </div>
  );
}
