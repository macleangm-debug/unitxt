import { useEffect, useMemo, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr } from "@/lib/api";
import {
  PageHeader, Card, Field, Input, Select, Btn, Pill, Stat, Table, Modal,
} from "@/components/UI";
import {
  Network, Plus, Zap, Activity, ShieldAlert, Gauge, Info,
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
function HealthChip({ h }) {
  const status = h?.status || "unconfigured";
  const label = h?.success_rate != null ? `${(h.success_rate * 100).toFixed(1)}%` : status;
  return (
    <span className={`inline-flex items-center gap-1.5 text-xs ${HEALTH_COLOR[status]}`}>
      <span className={`h-1.5 w-1.5 rounded-full ${HEALTH_DOT[status]}`}/>
      {label}
    </span>
  );
}

/* -------- Add-integration wizard — ONLY shows adapters actually built -------- */
function AddIntegrationWizard({ open, onClose, onCreated }) {
  const [available, setAvailable] = useState([]);
  const [pick, setPick] = useState(null);
  const [countries, setCountries] = useState([]);
  const [form, setForm] = useState({
    name: "", priority: 100, cost_per_sms: 0.01, channels: ["sms"],
    countries: [], operators: [], active: true,
    api_key: "", api_secret: "", base_url: "",
  });
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (!open) return;
    setPick(null);
    setForm({
      name: "", priority: 100, cost_per_sms: 0.01, channels: ["sms"],
      countries: [], operators: [], active: true,
      api_key: "", api_secret: "", base_url: "",
    });
    http.get("/admin/country-hub/integrations/available").then(r => setAvailable(r.data)).catch(() => {});
    http.get("/admin/country-hub").then(r => setCountries(r.data)).catch(() => {});
  }, [open]);

  if (!open) return null;

  const selectAdapter = (a) => {
    setPick(a);
    setForm(f => ({
      ...f,
      name: a.label,
      channels: a.channels || ["sms"],
      ...(a.fields || []).reduce((acc, fd) => {
        if (fd.default) acc[fd.key] = fd.default;
        return acc;
      }, {}),
    }));
  };

  const missingRequired = pick
    ? (pick.fields || []).filter(f => f.required && !form[f.key])
    : [];

  const submit = async () => {
    if (!pick) return;
    setBusy(true);
    try {
      const body = {
        name: form.name || pick.label,
        type: pick.kind === "mock" ? "aggregator" : "direct_telco",
        countries: form.countries,
        channels: form.channels,
        api_key: form.api_key || "",
        api_secret: form.api_secret || "",
        base_url: form.base_url || "",
        cost_per_sms: Number(form.cost_per_sms),
        priority: Number(form.priority),
        active: form.active,
        supports_dlr: true,
        supports_unicode: true,
      };
      await http.post("/admin/providers", body);
      toast.success(`${pick.label} added`);
      onCreated?.();
      onClose();
    } catch (err) {
      toast.error(fmtErr(err.response?.data?.detail));
    } finally { setBusy(false); }
  };

  const toggleCountry = (code) => {
    const has = form.countries.includes(code);
    setForm({ ...form, countries: has ? form.countries.filter(c => c !== code) : [...form.countries, code] });
  };

  return (
    <Modal open={open} onClose={onClose}
           title={pick ? `Configure ${pick.label}` : "Add integration"}
           testid="add-integration-wizard">
      {!pick && (
        <div data-testid="adapter-picker">
          <div className="label-overline mb-3">Pick a partner</div>
          <div className="flex items-start gap-2 border border-zinc-900 bg-[#0e0e0e] p-3 text-xs text-zinc-500">
            <Info className="mt-0.5 h-3.5 w-3.5 shrink-0"/>
            <span>Only adapters actually wired into this codebase are listed. We do not expose unintegrated partners.</span>
          </div>
          <div className="mt-3 grid gap-2">
            {available.map(a => (
              <button key={a.kind}
                      onClick={() => selectAdapter(a)}
                      data-testid={`pick-${a.kind}`}
                      className="flex items-start gap-3 border border-zinc-900 bg-[#141414] p-4 text-left transition hover:border-zinc-700">
                <Network className="h-5 w-5 text-white" strokeWidth={1.5}/>
                <div className="flex-1">
                  <div className="font-medium">{a.label}</div>
                  <div className="text-xs text-zinc-500">{a.description}</div>
                  <div className="mt-1 font-mono text-[11px] text-zinc-600">
                    channels: {(a.channels || []).join(", ") || "—"}
                  </div>
                </div>
              </button>
            ))}
            {available.length === 0 && (
              <div className="border border-dashed border-zinc-800 p-8 text-center text-sm text-zinc-500">
                No adapters are integrated yet.
              </div>
            )}
          </div>
        </div>
      )}

      {pick && (
        <div className="grid gap-3" data-testid="integration-form">
          <Field label="Display name">
            <Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })}
                   data-testid="int-name"/>
          </Field>

          {/* Adapter-specific cred fields */}
          {(pick.fields || []).map(fd => (
            <Field key={fd.key} label={`${fd.label}${fd.required ? " *" : ""}`}>
              <Input type={fd.type === "password" ? "password" : "text"}
                     value={form[fd.key] || ""}
                     onChange={(e) => setForm({ ...form, [fd.key]: e.target.value })}
                     data-testid={`int-${fd.key}`}/>
            </Field>
          ))}

          <div className="grid gap-3 sm:grid-cols-3">
            <Field label="Priority" hint="Lower = tried first">
              <Input type="number" value={form.priority}
                     onChange={(e) => setForm({ ...form, priority: e.target.value })}/>
            </Field>
            <Field label="Cost per SMS (USD)">
              <Input type="number" step="0.0001" value={form.cost_per_sms}
                     onChange={(e) => setForm({ ...form, cost_per_sms: e.target.value })}/>
            </Field>
            <Field label="Active on save">
              <Select value={form.active ? "1" : "0"}
                       onChange={(e) => setForm({ ...form, active: e.target.value === "1" })}>
                <option value="1">Yes</option><option value="0">No</option>
              </Select>
            </Field>
          </div>

          <Field label="Countries to cover" hint="Select at least one.">
            <div className="grid max-h-36 gap-1 overflow-y-auto border border-zinc-900 bg-[#0e0e0e] p-2 sm:grid-cols-3">
              {countries.length === 0 && (
                <div className="col-span-3 p-3 text-xs text-zinc-500">
                  No countries onboarded yet — add one from Country hub first.
                </div>
              )}
              {countries.map(c => {
                const on = form.countries.includes(c.code);
                return (
                  <button key={c.code}
                          onClick={() => toggleCountry(c.code)}
                          type="button"
                          data-testid={`cov-${c.code}`}
                          className={`flex items-center gap-2 border px-2 py-1 text-left text-xs transition ${
                            on ? "border-white bg-white/5 text-white"
                               : "border-zinc-900 text-zinc-500 hover:border-zinc-700"
                          }`}>
                    <span className={`h-2 w-2 rounded-full ${on ? "bg-white" : "bg-zinc-700"}`}/>
                    <span className="font-mono">{c.code}</span>
                    <span className="truncate">{c.name}</span>
                  </button>
                );
              })}
            </div>
          </Field>

          {missingRequired.length > 0 && (
            <div className="flex items-start gap-2 border border-amber-500/30 bg-amber-500/5 p-2 text-xs text-amber-300">
              <ShieldAlert className="mt-0.5 h-3.5 w-3.5 shrink-0"/>
              <span>Missing: {missingRequired.map(f => f.label).join(", ")}</span>
            </div>
          )}

          <div className="mt-2 flex items-center justify-between border-t border-zinc-900 pt-4">
            <Btn variant="ghost" onClick={() => setPick(null)}>Pick a different partner</Btn>
            <Btn onClick={submit}
                 disabled={busy || missingRequired.length > 0 || form.countries.length === 0}
                 data-testid="int-submit">
              {busy ? "Saving..." : "Save integration"}
            </Btn>
          </div>
        </div>
      )}
    </Modal>
  );
}

/* -------- Main page -------- */
export default function IntegrationHealth() {
  const [rows, setRows] = useState([]);
  const [loading, setLoading] = useState(false);
  const [wizard, setWizard] = useState(false);

  const load = async () => {
    setLoading(true);
    try { const r = await http.get("/admin/integrations/health"); setRows(r.data); }
    catch { /* noop */ }
    finally { setLoading(false); }
  };
  useEffect(() => { load(); }, []);

  const totals = useMemo(() => ({
    total: rows.length,
    healthy: rows.filter(r => r.health.status === "healthy").length,
    degraded: rows.filter(r => r.health.status === "degraded").length,
    down: rows.filter(r => r.health.status === "down").length,
    sent24h: rows.reduce((a, r) => a + (r.health.sent_24h || 0), 0),
  }), [rows]);

  const test = async (p) => {
    try {
      const r = await http.post(`/admin/integrations/${p.id}/test`);
      if (r.data.ok) toast.success(`${p.name} — OK (${r.data.latency_ms}ms)`);
      else toast.error(`${p.name} — ${r.data.error || "failed"}`);
      load();
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
  };

  return (
    <div>
      <PageHeader
        overline="Integrations"
        title="Integration health"
        desc="Every partner adapter, every country it covers, live success rate and 1-click test. Only the partners actually integrated with unitxt are available."
        actions={
          <Btn onClick={() => setWizard(true)} data-testid="add-integration-btn">
            <Plus className="h-4 w-4"/>Add integration
          </Btn>
        }
      />

      <div className="mb-4 grid grid-cols-2 gap-0 border border-zinc-900 sm:grid-cols-5">
        <Stat label="Integrations" value={totals.total} accent="white"/>
        <Stat label="Healthy"       value={totals.healthy} accent="green"/>
        <Stat label="Degraded"      value={totals.degraded} accent="orange"/>
        <Stat label="Down"          value={totals.down} accent="red"/>
        <Stat label="Sent last 24h" value={totals.sent24h.toLocaleString()}/>
      </div>

      <div className="mb-4 flex items-center justify-end">
        <Btn variant="ghost" onClick={load} disabled={loading}>
          <Activity className="h-4 w-4"/>{loading ? "Loading..." : "Refresh"}
        </Btn>
      </div>

      <Table
        testid="integrations-table"
        rows={rows}
        empty="No integrations yet — click Add integration."
        columns={[
          {
            key: "name", label: "Partner",
            render: r => (
              <div>
                <div className="text-sm font-medium text-white">{r.name}</div>
                <div className="font-mono text-[11px] text-zinc-500">{r.kind}</div>
              </div>
            ),
          },
          { key: "countries", label: "Countries", mono: true,
            render: r => (r.countries || []).join(", ") || "—" },
          { key: "channels", label: "Channels", mono: true,
            render: r => (r.channels || []).join("/") || "—" },
          { key: "priority", label: "Priority", mono: true },
          {
            key: "creds", label: "Credentials",
            render: r => <Pill status={r.health.creds ? "active" : "down"}>{r.health.creds ? "present" : "missing"}</Pill>,
          },
          {
            key: "health", label: "Health",
            render: r => <HealthChip h={r.health}/>,
          },
          { key: "sent", label: "Sent 24h", mono: true,
            render: r => (r.health.sent_24h || 0).toLocaleString() },
          {
            key: "active", label: "Active",
            render: r => <Pill status={r.active ? "active" : "down"}/>,
          },
          {
            key: "test", label: "",
            render: r => (
              <button onClick={() => test(r)}
                      className="inline-flex items-center gap-1 text-xs text-zinc-400 hover:text-white"
                      data-testid={`test-int-${r.id}`}>
                <Zap className="h-3 w-3"/>Test
              </button>
            ),
          },
        ]}
      />

      <Card className="mt-6">
        <div className="flex items-start gap-3">
          <Gauge className="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" strokeWidth={1.5}/>
          <div className="text-xs text-zinc-500">
            <div className="text-zinc-300">How health is computed</div>
            Success rate is aggregated from the last 24h of messages sent through each provider.
            A provider is <span className="text-emerald-400">healthy</span> at ≥90%,
            <span className="text-amber-400"> degraded</span> at 50-90% or when credentials are missing,
            <span className="text-red-400"> down</span> below 50% with traffic, and
            <span className="text-zinc-400"> idle</span> if no messages have been routed through it recently.
          </div>
        </div>
      </Card>

      <AddIntegrationWizard open={wizard} onClose={() => setWizard(false)} onCreated={load}/>
    </div>
  );
}
