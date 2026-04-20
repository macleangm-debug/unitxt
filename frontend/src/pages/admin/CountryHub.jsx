import { useEffect, useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import { toast } from "sonner";
import http, { fmtErr } from "@/lib/api";
import { PageHeader, Card, Field, Input, Select, Btn, Pill, Stat, Modal, Table } from "@/components/UI";
import {
  Globe2, Plus, Activity, Search, ArrowRight, Check,
} from "lucide-react";

const HEALTH_COLOR = {
  healthy:      "text-emerald-400",
  degraded:     "text-amber-400",
  down:         "text-red-400",
  idle:         "text-zinc-500",
  offline:      "text-zinc-600",
  unconfigured: "text-zinc-600",
};
const HEALTH_DOT = {
  healthy:      "bg-emerald-400",
  degraded:     "bg-amber-400",
  down:         "bg-red-400",
  idle:         "bg-zinc-500",
  offline:      "bg-zinc-600",
  unconfigured: "bg-zinc-600",
};

// Simple unicode flag from ISO-2; falls back to globe icon
function flagFor(code) {
  if (!code || code.length !== 2) return null;
  const A = 0x1f1e6;
  return String.fromCodePoint(...code.toUpperCase().split("").map(c => A + c.charCodeAt(0) - 65));
}

function HealthChip({ h }) {
  const status = h?.status || "unconfigured";
  const dot = HEALTH_DOT[status] || "bg-zinc-500";
  const color = HEALTH_COLOR[status] || "text-zinc-500";
  const label = h?.success_rate != null
    ? `${(h.success_rate * 100).toFixed(1)}%`
    : status;
  return (
    <span className={`inline-flex items-center gap-1.5 text-xs ${color}`}>
      <span className={`relative h-1.5 w-1.5 rounded-full ${dot}`}>
        {status === "healthy" && (
          <span className={`absolute inset-0 animate-ping rounded-full ${dot} opacity-75`}/>
        )}
      </span>
      {label}
    </span>
  );
}

/* ------------- Add-country wizard ------------- */
function AddCountryWizard({ open, onClose, onCreated }) {
  const [step, setStep] = useState(1);
  const [busy, setBusy] = useState(false);
  const [providers, setProviders] = useState([]);
  const empty = {
    code: "", name: "", dial_code: "+", currency: "USD", timezone: "",
    credits_per_sms: 2, credits_per_whatsapp: 3,
    routes: [],
    sender_id_required: true,
    opt_out_footer: "Reply STOP to opt out.",
    allowed_sender_patterns: [],
    daily_cap: 100000,
  };
  const [form, setForm] = useState(empty);

  useEffect(() => {
    if (open) {
      setStep(1); setForm(empty);
      http.get("/admin/providers").then(r => setProviders(r.data)).catch(() => {});
    }
    /* eslint-disable-next-line */
  }, [open]);

  if (!open) return null;

  const set = (patch) => setForm({ ...form, ...patch });
  const next = () => setStep(s => Math.min(4, s + 1));
  const prev = () => setStep(s => Math.max(1, s - 1));

  const toggleRoute = (pid, priority = 100) => {
    const exists = form.routes.find(r => r.provider_id === pid);
    const routes = exists
      ? form.routes.filter(r => r.provider_id !== pid)
      : [...form.routes, { provider_id: pid, priority, operators: [] }];
    set({ routes });
  };

  const submit = async () => {
    setBusy(true);
    try {
      const res = await http.post("/admin/country-hub/wizard", {
        ...form,
        code: form.code.toUpperCase(),
        credits_per_sms: Number(form.credits_per_sms),
        credits_per_whatsapp: Number(form.credits_per_whatsapp),
        daily_cap: Number(form.daily_cap),
      });
      toast.success(`${form.code.toUpperCase()} ${res.data.status === "active" ? "activated" : "saved as draft"}`);
      onCreated?.();
      onClose();
    } catch (err) {
      toast.error(fmtErr(err.response?.data?.detail));
    } finally { setBusy(false); }
  };

  const canNext = {
    1: form.code.trim().length === 2 && form.name.trim() && form.dial_code.trim().length > 1,
    2: Number(form.credits_per_sms) > 0 && Number(form.credits_per_whatsapp) > 0,
    3: form.routes.length > 0,
    4: true,
  }[step];

  return (
    <Modal open={open} onClose={onClose} title={`Add country — step ${step} of 4`} testid="country-wizard">
      {/* step bar */}
      <div className="mb-5 flex items-center gap-2 text-xs">
        {["Identity", "Pricing", "Routes", "Compliance"].map((label, i) => (
          <div key={label} className={`flex flex-1 items-center gap-2 border-b-2 pb-2 ${
            step === i + 1 ? "border-white text-white"
              : step > i + 1 ? "border-emerald-500 text-emerald-400"
              : "border-zinc-800 text-zinc-600"
          }`}>
            <span className="font-mono">{i + 1}</span>{label}
            {step > i + 1 && <Check className="h-3 w-3"/>}
          </div>
        ))}
      </div>

      {step === 1 && (
        <div className="grid gap-3 sm:grid-cols-2" data-testid="wiz-identity">
          <Field label="ISO-2 code" hint="e.g. TZ, KE, NG">
            <Input value={form.code} maxLength={2}
                   onChange={(e) => set({ code: e.target.value.toUpperCase() })}
                   data-testid="wiz-code" className="uppercase"/>
          </Field>
          <Field label="Country name">
            <Input value={form.name} onChange={(e) => set({ name: e.target.value })}
                   data-testid="wiz-name"/>
          </Field>
          <Field label="Dial code" hint="with leading + (e.g. +255)">
            <Input value={form.dial_code} onChange={(e) => set({ dial_code: e.target.value })}/>
          </Field>
          <Field label="Currency">
            <Input value={form.currency} onChange={(e) => set({ currency: e.target.value.toUpperCase() })}/>
          </Field>
          <Field label="Timezone" hint="IANA, e.g. Africa/Dar_es_Salaam">
            <Input value={form.timezone || ""} onChange={(e) => set({ timezone: e.target.value })}/>
          </Field>
        </div>
      )}

      {step === 2 && (
        <div className="grid gap-3 sm:grid-cols-2" data-testid="wiz-pricing">
          <Field label="Credits per SMS" hint="Client retail rate for this country">
            <Input type="number" min="1" value={form.credits_per_sms}
                   onChange={(e) => set({ credits_per_sms: e.target.value })}
                   data-testid="wiz-sms-rate"/>
          </Field>
          <Field label="Credits per WhatsApp msg">
            <Input type="number" min="1" value={form.credits_per_whatsapp}
                   onChange={(e) => set({ credits_per_whatsapp: e.target.value })}/>
          </Field>
        </div>
      )}

      {step === 3 && (
        <div data-testid="wiz-routes">
          <div className="label-overline mb-2">Pick at least one provider to route traffic</div>
          {providers.length === 0 && (
            <div className="border border-dashed border-zinc-800 p-6 text-center text-sm text-zinc-500">
              No providers configured yet — add one from <a href="/admin/integrations" className="text-white underline">Integration health</a> first.
            </div>
          )}
          <div className="grid gap-2 max-h-72 overflow-y-auto">
            {providers.map(p => {
              const on = form.routes.some(r => r.provider_id === p.id);
              return (
                <button key={p.id}
                        onClick={() => toggleRoute(p.id)}
                        data-testid={`wiz-route-${p.id}`}
                        className={`flex items-center gap-3 border p-3 text-left transition ${
                          on ? "border-white bg-white/5" : "border-zinc-900 bg-[#141414] hover:border-zinc-700"
                        }`}>
                  <div className={`grid h-5 w-5 place-items-center border ${on ? "border-white bg-white text-black" : "border-zinc-700"}`}>
                    {on && <Check className="h-3 w-3"/>}
                  </div>
                  <div className="flex-1">
                    <div className="text-sm font-medium">{p.name}</div>
                    <div className="font-mono text-[11px] text-zinc-500">
                      {(p.channels || []).join("/")} · priority {p.priority || 100} · ${p.cost_per_sms}/sms
                    </div>
                  </div>
                </button>
              );
            })}
          </div>
        </div>
      )}

      {step === 4 && (
        <div className="grid gap-3" data-testid="wiz-compliance">
          <Field label="Sender ID required?">
            <Select value={form.sender_id_required ? "1" : "0"}
                     onChange={(e) => set({ sender_id_required: e.target.value === "1" })}>
              <option value="1">Yes (branded only)</option>
              <option value="0">No (anonymous long-code allowed)</option>
            </Select>
          </Field>
          <Field label="Opt-out footer (required by most regulators)">
            <Input value={form.opt_out_footer}
                   onChange={(e) => set({ opt_out_footer: e.target.value })}/>
          </Field>
          <Field label="Allowed sender ID patterns" hint="Comma-separated regex. Leave empty for any.">
            <Input value={(form.allowed_sender_patterns || []).join(", ")}
                   onChange={(e) => set({ allowed_sender_patterns: e.target.value.split(",").map(s => s.trim()).filter(Boolean) })}/>
          </Field>
          <Field label="Daily cap (messages)">
            <Input type="number" value={form.daily_cap}
                   onChange={(e) => set({ daily_cap: e.target.value })}/>
          </Field>
        </div>
      )}

      <div className="mt-6 flex items-center justify-between border-t border-zinc-900 pt-4">
        <Btn variant="ghost" onClick={prev} disabled={step === 1}>Back</Btn>
        {step < 4 ? (
          <Btn onClick={next} disabled={!canNext} data-testid="wiz-next">
            Next <ArrowRight className="h-4 w-4"/>
          </Btn>
        ) : (
          <Btn onClick={submit} disabled={busy || !canNext} data-testid="wiz-submit">
            {busy ? "Saving..." : "Finish & activate"}
          </Btn>
        )}
      </div>
    </Modal>
  );
}

/* ------------- Main page ------------- */
export default function CountryHub() {
  const [rows, setRows] = useState([]);
  const [q, setQ] = useState("");
  const [wizard, setWizard] = useState(false);
  const nav = useNavigate();

  const load = () => http.get("/admin/country-hub").then(r => setRows(r.data)).catch(() => {});
  useEffect(() => { load(); }, []);

  const filtered = useMemo(() => {
    const needle = q.trim().toLowerCase();
    if (!needle) return rows;
    return rows.filter(r => [r.code, r.name, r.dial_code, r.status]
      .filter(Boolean).some(v => String(v).toLowerCase().includes(needle)));
  }, [rows, q]);

  const totals = useMemo(() => ({
    countries: rows.length,
    active: rows.filter(r => r.status === "active").length,
    healthy: rows.filter(r => r.health?.status === "healthy").length,
    unconfigured: rows.filter(r => r.health?.status === "unconfigured").length,
  }), [rows]);

  return (
    <div>
      <PageHeader
        overline="Geographies"
        title="Country hub"
        desc="Every country we operate in — pricing, routes, integrations, operators, sender IDs and health in one place."
        actions={
          <Btn onClick={() => setWizard(true)} data-testid="add-country-btn">
            <Plus className="h-4 w-4"/>Add country
          </Btn>
        }
      />

      <div className="mb-4 grid grid-cols-2 gap-0 border border-zinc-900 sm:grid-cols-4">
        <Stat label="Countries"   value={totals.countries} accent="white"/>
        <Stat label="Active"      value={totals.active} accent="green"/>
        <Stat label="Healthy"     value={totals.healthy} accent="green"/>
        <Stat label="Unconfigured" value={totals.unconfigured} accent="orange"/>
      </div>

      <div className="mb-4 flex items-center justify-between border border-zinc-900 bg-[#141414] px-3 py-2">
        <div className="relative max-w-sm flex-1">
          <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500" strokeWidth={1.5}/>
          <Input
            placeholder="Search code, name..."
            value={q}
            onChange={(e) => setQ(e.target.value)}
            className="pl-9"
            data-testid="country-search"
          />
        </div>
        <Btn variant="ghost" onClick={load}><Activity className="h-4 w-4"/>Refresh</Btn>
      </div>

      {filtered.length === 0 && (
        <div className="border border-dashed border-zinc-800 p-12 text-center text-sm text-zinc-500">
          No countries yet — hit "Add country" to onboard your first geography.
        </div>
      )}

      <Table
        testid="countries-table"
        rows={filtered}
        empty="No countries match."
        columns={[
          {
            key: "name", label: "Country",
            render: c => (
              <button onClick={() => nav(`/admin/country-hub/${c.code}`)}
                      className="flex items-center gap-3 text-left hover:text-white"
                      data-testid={`open-country-${c.code}`}>
                <span className="text-xl">{flagFor(c.code) || "🌐"}</span>
                <div>
                  <div className="text-sm font-medium text-white">{c.name}</div>
                  <div className="font-mono text-[11px] text-zinc-500">{c.code} · {c.dial_code}</div>
                </div>
              </button>
            ),
          },
          { key: "credits_per_sms", label: "Rate", mono: true, render: c => `${c.credits_per_sms} cr/SMS` },
          { key: "routes_count", label: "Routes", mono: true },
          { key: "operators_count", label: "Operators", mono: true },
          { key: "prefixes_count", label: "Prefixes", mono: true },
          {
            key: "sender_ids",
            label: "Sender IDs",
            mono: true,
            render: c => `${c.active_sender_ids}/${c.sender_ids_count}`,
          },
          {
            key: "health",
            label: "Health",
            render: c => <HealthChip h={c.health}/>,
          },
          {
            key: "status",
            label: "Status",
            render: c => (
              <Pill status={c.status === "active" ? "active"
                              : c.status === "draft" ? "pending"
                              : "down"}>{c.status}</Pill>
            ),
          },
          {
            key: "actions",
            label: "",
            render: c => (
              <Btn variant="ghost" onClick={() => nav(`/admin/country-hub/${c.code}`)}
                   data-testid={`cta-${c.code}`}>
                Manage →
              </Btn>
            ),
          },
        ]}
      />

      <AddCountryWizard open={wizard} onClose={() => setWizard(false)} onCreated={load}/>
    </div>
  );
}
