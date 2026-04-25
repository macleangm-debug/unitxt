import { useEffect, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { toast } from "sonner";
import http, { fmtErr } from "@/lib/api";
import {
  PageHeader, Card, Field, Input, Select, Btn, Pill, Stat, Table,
} from "@/components/UI";import {
  Globe2, ShieldCheck, Route as RouteIcon, Users, IdCard, Smartphone,
  Zap, PauseCircle, PlayCircle, ArrowLeft, Activity, Shield,
  TrendingUp, Plus, Trash2, Check,
} from "lucide-react";

const HEALTH_COLOR = {
  healthy: "text-emerald-400", degraded: "text-amber-400",
  down: "text-red-400", idle: "text-zinc-500",
  offline: "text-zinc-600", unconfigured: "text-zinc-600",
};
const HEALTH_DOT = {
  healthy: "bg-emerald-400", degraded: "bg-amber-400",
  down: "bg-red-400", idle: "bg-zinc-500",
  offline: "bg-zinc-600", unconfigured: "bg-zinc-600",
};
function flagFor(code) {
  if (!code || code.length !== 2) return null;
  const A = 0x1f1e6;
  return String.fromCodePoint(...code.toUpperCase().split("").map(c => A + c.charCodeAt(0) - 65));
}
function HealthChip({ h }) {
  const status = h?.status || "unconfigured";
  const label = h?.success_rate != null ? `${(h.success_rate * 100).toFixed(1)}%` : status;
  return (
    <span className={`inline-flex items-center gap-1.5 text-sm ${HEALTH_COLOR[status]}`}>
      <span className={`h-2 w-2 rounded-full ${HEALTH_DOT[status]}`}/>
      {label}
    </span>
  );
}

const TABS = [
  { key: "overview",   label: "Overview",       icon: Activity },
  { key: "economics",  label: "Economics",      icon: TrendingUp },
  { key: "routes",     label: "Routes",         icon: RouteIcon },
  { key: "operators",  label: "Operators & prefixes", icon: Smartphone },
  { key: "sender",     label: "Sender IDs",     icon: IdCard },
  { key: "compliance", label: "Compliance",     icon: ShieldCheck },
];

export default function CountryDetail() {
  const { code } = useParams();
  const nav = useNavigate();
  const [data, setData] = useState(null);
  const [tab, setTab] = useState("overview");
  const [busy, setBusy] = useState(false);

  const load = () => http.get(`/admin/country-hub/${code}`).then(r => setData(r.data))
                         .catch(err => toast.error(fmtErr(err.response?.data?.detail)));
  useEffect(() => { load(); /* eslint-disable-next-line */ }, [code]);

  if (!data) return <div className="p-6 text-sm text-zinc-500">Loading {code}…</div>;

  const c = data.country;
  const health = data.health;

  const setStatus = async (status) => {
    setBusy(true);
    try {
      await http.put(`/admin/country-hub/${code}/status`, { status });
      toast.success(`${code} is now ${status}`);
      load();
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
    finally { setBusy(false); }
  };

  const testProvider = async (pid, name) => {
    try {
      const r = await http.post(`/admin/integrations/${pid}/test`);
      if (r.data.ok) toast.success(`${name} — OK (${r.data.latency_ms}ms)`);
      else toast.error(`${name} — ${r.data.error || "failed"}`);
      load();
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
  };

  return (
    <div>
      <button onClick={() => nav("/admin/country-hub")}
              className="mb-4 inline-flex items-center gap-1 text-xs text-zinc-500 hover:text-white">
        <ArrowLeft className="h-3 w-3"/>Country hub
      </button>

      <PageHeader
        overline={`${c.dial_code} · ${c.currency}${c.timezone ? ` · ${c.timezone}` : ""}`}
        title={<span className="inline-flex items-center gap-3">
          <span className="text-3xl">{flagFor(code) || <Globe2 className="h-7 w-7"/>}</span>
          {c.name}
          <Pill status={c.status === "active" ? "active" : c.status === "draft" ? "pending" : "down"}>{c.status}</Pill>
        </span>}
        desc="Country-level cockpit — live health, routes, operators, sender IDs, compliance and a kill switch."
        actions={
          c.status === "active" ? (
            <Btn variant="danger" onClick={() => setStatus("paused")} disabled={busy} data-testid="kill-switch">
              <PauseCircle className="h-4 w-4"/>Kill switch
            </Btn>
          ) : (
            <Btn onClick={() => setStatus("active")} disabled={busy} data-testid="activate-country">
              <PlayCircle className="h-4 w-4"/>Activate
            </Btn>
          )
        }
      />

      {/* Tab bar */}
      <div className="mb-4 flex flex-wrap gap-2 border-b border-zinc-900 pb-3" data-testid="country-tabs">
        {TABS.map(t => (
          <button key={t.key} onClick={() => setTab(t.key)}
                  data-testid={`ctab-${t.key}`}
                  className={`inline-flex items-center gap-2 border px-4 py-2 text-sm transition-all ${
                    tab === t.key ? "border-white bg-white text-black"
                                  : "border-zinc-900 bg-[#0e0e0e] text-zinc-400 hover:border-zinc-700 hover:text-white"}`}>
            <t.icon className="h-4 w-4" strokeWidth={1.5}/>{t.label}
          </button>
        ))}
      </div>

      {/* OVERVIEW */}
      {tab === "overview" && (
        <div className="space-y-4" data-testid="ov-panel">
          <div className="grid grid-cols-2 gap-0 border border-zinc-900 sm:grid-cols-4">
            <Stat label="Health"         value={<HealthChip h={health}/>}/>
            <Stat label="Sent 24h"       value={health.sent_24h ?? 0}/>
            <Stat label="Active routes"  value={`${health.providers_healthy}/${health.providers_total}`}/>
            <Stat label="SMS rate"       value={`${data.credits_per_sms} cr`}/>
          </div>
          <Card>
            <div className="label-overline mb-2">Summary</div>
            <div className="grid gap-2 text-sm text-zinc-400 sm:grid-cols-2">
              <div>Dial code: <span className="font-mono text-white">{c.dial_code}</span></div>
              <div>Currency: <span className="font-mono text-white">{c.currency}</span></div>
              <div>Timezone: <span className="font-mono text-white">{c.timezone || "—"}</span></div>
              <div>Sender ID required: <span className="font-mono text-white">{c.sender_id_required ? "yes" : "no"}</span></div>
              <div>Daily cap: <span className="font-mono text-white">{c.daily_cap?.toLocaleString?.() || "—"}</span></div>
              <div>Routes: <span className="font-mono text-white">{data.routes.length}</span></div>
              <div>Operators: <span className="font-mono text-white">{data.operators.length}</span></div>
              <div>Sender IDs: <span className="font-mono text-white">{data.sender_ids.length}</span></div>
            </div>
          </Card>
        </div>
      )}

      {/* ROUTES */}
      {tab === "routes" && (
        <div data-testid="routes-panel">
          <Table
            testid="routes-table"
            rows={data.routes}
            empty="No routes yet — add a provider via Integration health and include this country."
            columns={[
              { key: "name", label: "Provider", render: r => <span className="text-white">{r.name}</span> },
              { key: "kind", label: "Kind", mono: true, render: r => r.kind },
              { key: "priority", label: "Priority", mono: true, render: r => r.priority ?? 100 },
              { key: "operators", label: "Operators", mono: true,
                render: r => (r.operators && r.operators.length ? r.operators.join(", ") : "any") },
              { key: "cost_per_sms", label: "Cost / SMS", mono: true,
                render: r => `$${(r.cost_per_sms ?? 0).toFixed(4)}` },
              { key: "success", label: "Success 24h", mono: true,
                render: r => r.health.success_rate != null
                  ? `${(r.health.success_rate * 100).toFixed(1)}%`
                  : "—" },
              { key: "health", label: "Status", render: r => <HealthChip h={r.health}/> },
              { key: "active", label: "Active", render: r => <Pill status={r.active ? "active" : "down"}/> },
              {
                key: "test", label: "",
                render: r => (
                  <button onClick={() => testProvider(r.id, r.name)}
                          className="inline-flex items-center gap-1 text-xs text-zinc-400 hover:text-white"
                          data-testid={`test-${r.id}`}>
                    <Zap className="h-3 w-3"/>Test
                  </button>
                ),
              },
            ]}
          />
        </div>
      )}

      {/* ECONOMICS */}
      {tab === "economics" && (
        <EconomicsPanel code={code} />
      )}

      {/* OPERATORS */}
      {tab === "operators" && (
        <OperatorsPanel code={code} data={data} onSaved={load}/>
      )}

      {/* SENDER IDs */}
      {tab === "sender" && (
        <SenderIdPanel rows={data.sender_ids} onDone={load}/>
      )}

      {/* COMPLIANCE */}
      {tab === "compliance" && (
        <CompliancePanel country={c} code={code} onSaved={load}/>
      )}
    </div>
  );
}

function CompliancePanel({ country, code, onSaved }) {
  const [form, setForm] = useState({
    opt_out_footer: country.opt_out_footer || "",
    allowed_sender_patterns: (country.allowed_sender_patterns || []).join(", "),
    daily_cap: country.daily_cap ?? 100000,
    sender_id_required: !!country.sender_id_required,
  });
  const [busy, setBusy] = useState(false);

  const save = async () => {
    setBusy(true);
    try {
      await http.put(`/admin/country-hub/${code}/compliance`, {
        opt_out_footer: form.opt_out_footer,
        allowed_sender_patterns: form.allowed_sender_patterns.split(",").map(s => s.trim()).filter(Boolean),
        daily_cap: Number(form.daily_cap),
        sender_id_required: form.sender_id_required,
      });
      toast.success("Compliance updated");
      onSaved?.();
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
    finally { setBusy(false); }
  };

  return (
    <Card testid="compliance-panel">
      <div className="label-overline mb-3 flex items-center gap-2">
        <Shield className="h-3.5 w-3.5"/>Compliance
      </div>
      <div className="grid gap-3">
        <Field label="Opt-out footer" hint="Mandatory in most jurisdictions.">
          <Input value={form.opt_out_footer}
                 onChange={(e) => setForm({ ...form, opt_out_footer: e.target.value })}
                 data-testid="opt-out-footer"/>
        </Field>
        <Field label="Allowed sender ID patterns" hint="Comma-separated regex. Empty = any sender allowed.">
          <Input value={form.allowed_sender_patterns}
                 onChange={(e) => setForm({ ...form, allowed_sender_patterns: e.target.value })}/>
        </Field>
        <Field label="Daily cap (messages)">
          <Input type="number" value={form.daily_cap}
                 onChange={(e) => setForm({ ...form, daily_cap: e.target.value })}/>
        </Field>
        <Field label="Sender ID required?">
          <Select value={form.sender_id_required ? "1" : "0"}
                   onChange={(e) => setForm({ ...form, sender_id_required: e.target.value === "1" })}>
            <option value="1">Yes</option><option value="0">No</option>
          </Select>
        </Field>
        <div>
          <Btn onClick={save} disabled={busy} data-testid="save-compliance">
            {busy ? "Saving..." : "Save compliance"}
          </Btn>
        </div>
      </div>
    </Card>
  );
}


/* ---------- Economics panel ---------- */
function EconomicsPanel({ code }) {
  const [data, setData] = useState(null);
  const [rate, setRate] = useState("");
  const [busy, setBusy] = useState(false);
  const [usdPerCredit, setUsdPerCredit] = useState("");

  const load = async () => {
    const r = await http.get(`/admin/country-hub/${code}/economics`);
    setData(r.data);
    setRate(r.data.unit_economics.credits_per_sms);
    setUsdPerCredit(r.data.unit_economics.usd_per_credit);
  };
  useEffect(() => { load(); /* eslint-disable-next-line */ }, [code]);

  if (!data) return <div className="text-sm text-zinc-500">Loading economics…</div>;
  const u = data.unit_economics;

  const saveRate = async () => {
    setBusy(true);
    try {
      await http.put(`/admin/country-hub/${code}/rate`, { credits_per_sms: Number(rate) });
      toast.success("Rate updated");
      load();
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
    finally { setBusy(false); }
  };

  const saveUsdPerCredit = async () => {
    setBusy(true);
    try {
      await http.put("/admin/settings",
        { key: "economy.usd_per_credit", value: Number(usdPerCredit), category: "economy" });
      toast.success("Credit value updated (global)");
      load();
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
    finally { setBusy(false); }
  };

  const fmtUsd = (n) => n == null ? "—" : `$${Number(n).toFixed(4)}`;
  const fmtUsd2 = (n) => n == null ? "—" : `$${Number(n).toFixed(2)}`;

  return (
    <div className="space-y-4" data-testid="economics-panel">
      {/* Unit economics */}
      <Card testid="unit-economics">
        <div className="label-overline mb-3 flex items-center gap-2">
          <TrendingUp className="h-3.5 w-3.5"/>Unit economics
        </div>
        <div className="grid grid-cols-2 gap-0 border border-zinc-900 md:grid-cols-4">
          <Stat label="Credits / SMS"       value={u.credits_per_sms}/>
          <Stat label="Retail / SMS (USD)"  value={fmtUsd(u.retail_usd_per_sms)} accent="white"/>
          <Stat label="Our cost avg (USD)"  value={fmtUsd(u.cost_usd_per_sms_avg)}/>
          <Stat label="Margin / SMS"        value={fmtUsd(u.margin_usd_per_sms)} accent={u.margin_pct > 0 ? "green" : "red"}/>
          <Stat label="Margin %"            value={u.margin_pct != null ? `${u.margin_pct}%` : "—"} accent={u.margin_pct > 0 ? "green" : "red"}/>
          <Stat label="Cost min"            value={fmtUsd(u.cost_usd_per_sms_min)}/>
          <Stat label="Cost max"            value={fmtUsd(u.cost_usd_per_sms_max)}/>
          <Stat label="Active routes"       value={u.providers_count}/>
        </div>

        <div className="mt-5 grid gap-3 sm:grid-cols-2">
          <div className="grid items-end gap-3 sm:grid-cols-[1fr,auto] border border-zinc-900 bg-[#141414] p-3">
            <Field label={`Credits / SMS for ${code}`} hint="Client retail rate in credits.">
              <Input type="number" value={rate} onChange={(e) => setRate(e.target.value)} data-testid="rate-input"/>
            </Field>
            <Btn onClick={saveRate} disabled={busy} className="h-10" data-testid="rate-save">Save rate</Btn>
          </div>
          <div className="grid items-end gap-3 sm:grid-cols-[1fr,auto] border border-zinc-900 bg-[#141414] p-3">
            <Field label="USD per credit (global)" hint="Reference rate for all countries. Changes propagate everywhere.">
              <Input type="number" step="0.0001" value={usdPerCredit} onChange={(e) => setUsdPerCredit(e.target.value)} data-testid="usd-per-credit"/>
            </Field>
            <Btn onClick={saveUsdPerCredit} disabled={busy} className="h-10" data-testid="usd-per-credit-save">Save</Btn>
          </div>
        </div>
      </Card>

      {/* P&L windows */}
      <Card testid="pnl-windows">
        <div className="label-overline mb-3">P&amp;L windows</div>
        <Table rows={data.windows} rowKey="days"
               empty="No traffic yet."
               columns={[
                 { key: "days", label: "Window", mono: true, render: r => `${r.days}d` },
                 { key: "sends", label: "Sends", mono: true, render: r => (r.sends || 0).toLocaleString() },
                 { key: "credits", label: "Credits used", mono: true, render: r => (r.credits || 0).toLocaleString() },
                 { key: "revenue_usd", label: "Revenue", mono: true, render: r => fmtUsd2(r.revenue_usd) },
                 { key: "cost_usd", label: "Cost", mono: true, render: r => fmtUsd2(r.cost_usd) },
                 { key: "margin_usd", label: "Margin", mono: true, render: r =>
                   <span className={(r.margin_usd || 0) >= 0 ? "text-emerald-400" : "text-red-400"}>
                     {fmtUsd2(r.margin_usd)}
                   </span>,
                 },
                 { key: "margin_pct", label: "Margin %", mono: true,
                   render: r => r.margin_pct != null ? `${r.margin_pct}%` : "—" },
               ]}/>
      </Card>

      <LocalEconomicsCard code={code} />
    </div>
  );
}

/* ---------- Local-currency economics (VAT + sell + wholesale) ---------- */
function LocalEconomicsCard({ code }) {
  const [v, setV] = useState(null);
  const [busy, setBusy] = useState(false);

  const load = () => http.get(`/admin/country-economics/${code}`).then(r => setV(r.data)).catch(() => {});
  useEffect(() => { load(); /* eslint-disable-next-line */ }, [code]);

  if (!v) return null;
  const set = (k, val) => setV({ ...v, [k]: val });

  // Live margin preview math
  const sell  = Number(v.sell_per_sms_local) || 0;
  const vat   = Number(v.vat_rate_pct) || 0;
  // Approx pre-VAT cost = wholesale (placeholder); real cost is per-route, see Routes
  const whole = Number(v.wholesale_per_sms_local) || 0;
  const trueCostFromWhole = whole * (1 + vat / 100);
  const margin = sell - trueCostFromWhole;
  const marginPct = sell ? (margin / sell) * 100 : 0;

  const save = async () => {
    setBusy(true);
    try {
      await http.put(`/admin/country-economics/${code}`, {
        vat_rate_pct: Number(v.vat_rate_pct || 0),
        sell_per_sms_local: Number(v.sell_per_sms_local || 0),
        wholesale_per_sms_local: Number(v.wholesale_per_sms_local || 0),
      });
      toast.success("Country economics saved");
      load();
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
    finally { setBusy(false); }
  };

  return (
    <Card testid="local-economics-card">
      <div className="flex items-start justify-between gap-3">
        <div>
          <div className="label-overline">Local-currency economics</div>
          <h3 className="mt-1 font-display text-base font-semibold">
            Set prices in {v.currency || "local currency"} — system handles credit math.
          </h3>
          <p className="mt-1 max-w-xl text-xs text-zinc-500">
            VAT applies to your buy (cost) side. Per-route buy prices live in
            Settings hub → Routes & operators. Selling price is what your direct
            clients pay (VAT-inclusive).
          </p>
        </div>
      </div>

      <div className="mt-4 grid gap-3 sm:grid-cols-3">
        <Field label={`VAT rate (%)`} hint="Country VAT applied to operator-cost side.">
          <Input type="number" step="0.5" min="0" max="100"
                  value={v.vat_rate_pct}
                  onChange={(e) => set("vat_rate_pct", e.target.value)}
                  data-testid="econ-vat"/>
        </Field>
        <Field label={`Sell / SMS (${v.currency || "local"})`}
                hint="What your direct clients pay per SMS, gross / VAT-incl.">
          <Input type="number" step="0.5" min="0"
                  value={v.sell_per_sms_local}
                  onChange={(e) => set("sell_per_sms_local", e.target.value)}
                  data-testid="econ-sell"/>
        </Field>
        <Field label={`Wholesale / SMS (${v.currency || "local"})`}
                hint="Reference rate used for affiliate / quote calculations.">
          <Input type="number" step="0.5" min="0"
                  value={v.wholesale_per_sms_local}
                  onChange={(e) => set("wholesale_per_sms_local", e.target.value)}
                  data-testid="econ-wholesale"/>
        </Field>
      </div>

      {/* Live margin preview */}
      {sell > 0 && (
        <div className="mt-4 rounded-md border border-blue-500/20 bg-blue-500/[0.04] p-3 text-[12px] leading-relaxed text-blue-200/90">
          <span className="font-medium text-blue-300">Live preview:</span>
          {" "}clients in {code} pay <strong>{v.currency} {sell.toLocaleString()}</strong> per SMS.
          {" "}Wholesale {v.currency} {whole.toLocaleString()} + {vat}% VAT = <strong>{v.currency} {trueCostFromWhole.toFixed(2)}</strong> true cost.
          {" "}Gross profit per SMS = <strong className={margin >= 0 ? "text-emerald-300" : "text-red-300"}>{v.currency} {margin.toFixed(2)} ({marginPct.toFixed(1)}%)</strong>.
        </div>
      )}

      <div className="mt-4 flex justify-end">
        <Btn onClick={save} disabled={busy} data-testid="econ-save">
          {busy ? "Saving…" : "Save economics"}
        </Btn>
      </div>
    </Card>
  );
}

/* ---------- Operators & prefixes panel (with add/delete) ---------- */
function OperatorsPanel({ code, data, onSaved }) {
  const [prefix, setPrefix] = useState("");
  const [operator, setOperator] = useState("");
  const [busy, setBusy] = useState(false);

  const add = async () => {
    setBusy(true);
    try {
      await http.post(`/admin/country-hub/${code}/prefixes`, { prefix: prefix.trim(), operator: operator.trim() || "Unknown", active: true });
      setPrefix(""); setOperator("");
      toast.success("Prefix added");
      onSaved?.();
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
    finally { setBusy(false); }
  };

  const del = async (pid) => {
    if (!window.confirm("Remove this prefix?")) return;
    try {
      await http.delete(`/admin/country-hub/${code}/prefixes/${pid}`);
      toast.success("Removed");
      onSaved?.();
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
  };

  return (
    <div className="grid gap-4 lg:grid-cols-[1fr,2fr]" data-testid="ops-panel">
      <Card>
        <div className="label-overline mb-3">Operators ({data.operators.length})</div>
        <Table rows={data.operators} rowKey="name"
               empty="No operators mapped yet — add prefixes to auto-populate."
               columns={[
                 { key: "name", label: "Operator", render: r => <span className="text-white">{r.name}</span> },
                 { key: "prefixes", label: "Prefixes", mono: true },
               ]}/>
      </Card>
      <Card>
        <div className="label-overline mb-3">Mobile prefixes ({data.prefixes.length})</div>
        <div className="mb-3 grid gap-2 border border-zinc-900 bg-[#141414] p-3 sm:grid-cols-[1fr,1fr,auto]">
          <Field label="Prefix" hint="e.g. +25571">
            <Input value={prefix} onChange={(e) => setPrefix(e.target.value)} data-testid="prefix-input"/>
          </Field>
          <Field label="Operator">
            <Input value={operator} onChange={(e) => setOperator(e.target.value)} placeholder="Tigo, Airtel, Vodacom..." data-testid="operator-input"/>
          </Field>
          <Btn onClick={add} disabled={busy || !prefix.trim()} className="h-10 self-end" data-testid="add-prefix-btn">
            <Plus className="h-4 w-4"/>Add
          </Btn>
        </div>
        <Table rows={data.prefixes.slice(0, 200)}
               empty="No prefixes yet."
               columns={[
                 { key: "prefix", label: "Prefix", mono: true },
                 { key: "operator", label: "Operator" },
                 { key: "active", label: "Active", render: r => <Pill status={r.active ? "active" : "down"}/> },
                 { key: "del", label: "",
                   render: r => (
                     <button onClick={() => del(r.id)} className="text-zinc-400 hover:text-red-400" data-testid={`del-prefix-${r.id}`}>
                       <Trash2 className="h-4 w-4"/>
                     </button>
                   ),
                 },
               ]}/>
      </Card>
    </div>
  );
}

/* ---------- Sender ID panel with approve/reject ---------- */
function SenderIdPanel({ rows, onDone }) {
  const review = async (sid, status) => {
    try {
      await http.post(`/admin/sender-ids/${sid.id}/review`, { status, note: "" });
      toast.success(`Marked ${status}`);
      onDone?.();
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
  };

  return (
    <Table testid="sender-ids-table"
           rows={rows}
           empty="No sender IDs in this country yet."
           columns={[
             { key: "sender_id", label: "Sender ID", mono: true },
             { key: "user_id", label: "Owner", mono: true, render: r => (r.user_id || "").slice(0, 8) },
             { key: "status", label: "Status", render: r => <Pill status={r.status}/> },
             { key: "created_at", label: "Created", mono: true,
               render: r => new Date(r.created_at).toLocaleDateString() },
             { key: "expires_at", label: "Expires", mono: true,
               render: r => r.expires_at ? new Date(r.expires_at).toLocaleDateString() : "—" },
             { key: "actions", label: "",
               render: r => r.status === "pending" ? (
                 <div className="flex gap-2">
                   <button onClick={() => review(r, "approved")}
                           className="inline-flex items-center gap-1 text-xs text-emerald-400 hover:text-emerald-300"
                           data-testid={`sid-approve-${r.id}`}>
                     <Check className="h-3 w-3"/>Approve
                   </button>
                   <button onClick={() => review(r, "rejected")}
                           className="inline-flex items-center gap-1 text-xs text-red-400 hover:text-red-300"
                           data-testid={`sid-reject-${r.id}`}>
                     <ArrowLeft className="h-3 w-3 rotate-180"/>Reject
                   </button>
                 </div>
               ) : null,
             },
           ]}/>
  );
}
