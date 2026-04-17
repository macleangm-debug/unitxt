import { useEffect, useState, useMemo } from "react";
import { toast } from "sonner";
import http, { fmtErr } from "@/lib/api";
import { PageHeader, Card, Field, Input, Select, TextArea, Btn, Pill } from "@/components/UI";
import {
  Settings as SettingsIcon, Globe2, Network, DollarSign, Wallet as WalletIcon,
  IdCard, Building2, Bell, ShieldCheck, Megaphone, Save, ChevronRight,
} from "lucide-react";

const CATEGORIES = [
  { key: "platform", label: "Platform", icon: SettingsIcon, desc: "Brand, currency, timezone, maintenance." },
  { key: "onboarding", label: "Onboarding", icon: Globe2, desc: "Signup flow, KYC requirements." },
  { key: "compliance", label: "Compliance", icon: ShieldCheck, desc: "Spam, KYC, daily limits, retention." },
  { key: "notifications", label: "Notifications", icon: Bell, desc: "Triggers, thresholds, templates." },
  { key: "providers", label: "Providers", icon: Network, desc: "Adapter configuration & failover.", linkTo: "/admin/providers" },
  { key: "countries", label: "Countries", icon: Globe2, desc: "Geographies and dial codes.", linkTo: "/admin/countries" },
  { key: "pricing", label: "Pricing", icon: DollarSign, desc: "Per-country, per-channel rates.", linkTo: "/admin/pricing" },
  { key: "wallets", label: "Wallets", icon: WalletIcon, desc: "All accounts.", linkTo: "/admin/wallets" },
  { key: "sender_ids", label: "Sender IDs", icon: IdCard, desc: "Approval queue & policies.", linkTo: "/admin/sender-ids" },
  { key: "institutions", label: "Institutions", icon: Building2, desc: "Banks, fintechs, mobile money.", linkTo: "/admin/institutions" },
  { key: "promotions", label: "Promotions", icon: Megaphone, desc: "Bonus credits & promo codes.", linkTo: "/admin/promotions" },
];

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
  return <Input value={v ?? ""} onChange={(e) => onChange(e.target.value)} />;
}

export default function SettingsHub() {
  const [active, setActive] = useState("platform");
  const [settings, setSettings] = useState([]);
  const [draft, setDraft] = useState({}); // key -> new value
  const [busy, setBusy] = useState(false);

  const load = () => http.get("/admin/settings").then(r => setSettings(r.data)).catch(()=>{});
  useEffect(load, []);

  const visible = useMemo(() => settings.filter(s => s.category === active), [settings, active]);

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

  const cat = CATEGORIES.find(c => c.key === active);

  return (
    <div>
      <PageHeader overline="Configuration" title="Settings hub"
        desc="The brain of unitxt. Almost every behaviour on the platform is driven from here."/>

      <div className="grid gap-0 border border-zinc-900 lg:grid-cols-[280px,1fr]">
        {/* LEFT RAIL */}
        <div className="border-b border-zinc-900 lg:border-b-0 lg:border-r">
          {CATEGORIES.map(c => (
            <button
              key={c.key}
              onClick={() => setActive(c.key)}
              data-testid={`settings-tab-${c.key}`}
              className={`group flex w-full items-center gap-3 px-5 py-3.5 text-left transition-all border-l-2 ${
                active === c.key
                  ? "tracing-beam-active text-white"
                  : "border-transparent text-zinc-400 hover:bg-white/5 hover:text-white"
              }`}
            >
              <c.icon className="h-4 w-4" strokeWidth={1.5}/>
              <div className="flex-1">
                <div className="text-sm font-medium">{c.label}</div>
                <div className="text-[11px] text-zinc-500">{c.desc}</div>
              </div>
              <ChevronRight className="h-3.5 w-3.5 text-zinc-700 group-hover:text-zinc-400"/>
            </button>
          ))}
        </div>

        {/* RIGHT PANEL */}
        <div className="p-6">
          <div className="flex items-start justify-between border-b border-zinc-900 pb-4">
            <div>
              <div className="label-overline">Category</div>
              <h2 className="mt-1 font-display text-2xl font-semibold tracking-tight">{cat?.label}</h2>
              <p className="mt-1 max-w-md text-sm text-zinc-500">{cat?.desc}</p>
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
                {cat.label} has a full dedicated workspace with rich filters, bulk actions, and forms. Open
                it from the link above.
              </p>
              <a href={cat.linkTo}><Btn className="mt-5"><cat.icon className="h-4 w-4"/>Open {cat.label}</Btn></a>
            </div>
          ) : (
            <div className="mt-6 space-y-4">
              {visible.length === 0 && (
                <div className="border border-dashed border-zinc-800 p-12 text-center text-sm text-zinc-500">
                  No keys yet in this category.
                </div>
              )}
              {visible.map(s => {
                const isDraft = s.key in draft;
                return (
                  <div key={s.key} className="grid gap-3 border border-zinc-900 bg-[#141414] p-4 sm:grid-cols-[1fr,1.5fr,auto] sm:items-end" data-testid={`setting-${s.key}`}>
                    <div>
                      <div className="font-mono text-[11px] uppercase tracking-widest text-zinc-500">key</div>
                      <code className="mt-1 block font-mono text-sm text-white">{s.key}</code>
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
          <Pill status="info">CONFIG SPINE</Pill>
          <div className="text-sm text-zinc-400">
            Add new settings keys from any module. The hub auto-renders editors based on value type
            (boolean, number, list, string).
          </div>
        </div>
      </Card>
    </div>
  );
}
