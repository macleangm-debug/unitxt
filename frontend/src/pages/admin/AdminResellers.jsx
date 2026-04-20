import { useEffect, useMemo, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr } from "@/lib/api";
import {
  PageHeader, Card, Field, Input, Select, Btn, Table, Pill, Modal, Stat,
} from "@/components/UI";
import {
  Users, Percent, Wallet as WalletIcon, ShieldCheck, Plus, Minus,
  PauseCircle, PlayCircle, Search, Activity, Copy, BarChart3,
} from "lucide-react";

const TABS = [
  { key: "catalog",  label: "Reseller catalog",   icon: Users },
  { key: "policy",   label: "Global policy",      icon: ShieldCheck },
  { key: "audit",    label: "Commission audit",   icon: BarChart3 },
];

const COUNTRIES = ["*", "TZ", "KE", "UG", "ZM", "GH", "NG", "ZA", "RW", "US", "GB", "IN", "AE"];

const pct = (x) => `${(Number(x || 0) * 100).toFixed(1)}%`;

function fmtNum(n) {
  return new Intl.NumberFormat().format(Math.round(Number(n || 0)));
}

/* ------------------------- Reseller drawer ------------------------- */
function ResellerDrawer({ reseller, onClose, onMutate }) {
  const [detail, setDetail] = useState(null);
  const [busy, setBusy] = useState(false);
  const [floatAmt, setFloatAmt] = useState(0);
  const [floatNote, setFloatNote] = useState("");
  const [defaultRate, setDefaultRate] = useState(0.15);
  const [override, setOverride] = useState({ country: "TZ", channel: "sms", commission_rate: 0.15, active: true });

  const load = async () => {
    const r = await http.get(`/admin/resellers/${reseller.id}/detail`);
    setDetail(r.data);
    setDefaultRate(r.data.reseller?.commission_rate ?? 0.15);
  };
  useEffect(() => { if (reseller?.id) load(); /* eslint-disable-next-line */ }, [reseller?.id]);

  if (!reseller) return null;

  const run = async (fn) => {
    setBusy(true);
    try { await fn(); toast.success("Saved"); await load(); onMutate?.(); }
    catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
    finally { setBusy(false); }
  };

  const adjustFloat = () =>
    run(async () => {
      const n = Number(floatAmt);
      if (!n) throw { response: { data: { detail: "Enter non-zero amount" } } };
      await http.post(`/admin/resellers/${reseller.id}/float`,
                      { amount: n, note: floatNote });
      setFloatAmt(0); setFloatNote("");
    });

  const saveDefault = () =>
    run(() => http.put(`/admin/resellers/${reseller.id}/default-commission`,
                        { commission_rate: Number(defaultRate) }));

  const addOverride = () =>
    run(() => http.post(`/admin/resellers/${reseller.id}/commissions`, {
      ...override,
      commission_rate: Number(override.commission_rate),
    }));

  const delOverride = (rid) =>
    run(() => http.delete(`/admin/resellers/${reseller.id}/commissions/${rid}`));

  const toggleStatus = () => {
    const next = reseller.status === "active" ? "suspended" : "active";
    run(() => http.put(`/admin/resellers/${reseller.id}/status`, { status: next }));
  };

  const copyCode = () => {
    navigator.clipboard?.writeText(reseller.reseller_code || "");
    toast.success("Referral code copied");
  };

  return (
    <div
      className="fixed inset-0 z-[100] flex justify-end bg-black/70"
      onClick={onClose}
      data-testid="reseller-drawer"
    >
      <div
        className="h-full w-full max-w-3xl overflow-y-auto border-l border-zinc-800 bg-[#0e0e0e]"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="sticky top-0 z-10 flex items-center justify-between border-b border-zinc-900 bg-[#0e0e0e]/95 px-6 py-4 backdrop-blur">
          <div>
            <div className="label-overline">Reseller</div>
            <h2 className="mt-1 font-display text-xl font-semibold tracking-tight">
              {reseller.name || reseller.email}
            </h2>
            <div className="mt-0.5 text-xs text-zinc-500">
              {reseller.email} · <code className="font-mono text-zinc-400">{reseller.reseller_code}</code>
              <button onClick={copyCode} className="ml-1 inline-flex h-4 w-4 items-center justify-center text-zinc-500 hover:text-white" data-testid="copy-code">
                <Copy className="h-3 w-3"/>
              </button>
            </div>
          </div>
          <button onClick={onClose} className="text-zinc-400 hover:text-white" data-testid="drawer-close">✕</button>
        </div>

        <div className="grid gap-4 p-6">
          <div className="grid grid-cols-2 gap-0 border border-zinc-900 sm:grid-cols-4">
            <Stat label="Float balance"        value={`${fmtNum(reseller.float_balance)} cr`} accent="white"/>
            <Stat label="Sub-clients"          value={fmtNum(reseller.clients_count)}/>
            <Stat label="Lifetime commission"  value={`${fmtNum(reseller.lifetime_commission)} cr`}/>
            <Stat label="Status"               value={reseller.status}/>
          </div>

          {/* Status toggle */}
          <div className="flex items-center justify-between border border-zinc-900 bg-[#141414] p-4">
            <div>
              <div className="font-medium">Account status</div>
              <div className="text-xs text-zinc-500">Suspend to block this reseller from sending, servicing new clients, or logging in.</div>
            </div>
            <Btn variant="ghost" onClick={toggleStatus} disabled={busy} data-testid="toggle-status">
              {reseller.status === "active"
                ? (<><PauseCircle className="h-4 w-4"/>Suspend</>)
                : (<><PlayCircle className="h-4 w-4"/>Reactivate</>)}
            </Btn>
          </div>

          {/* Float top-up / clawback */}
          <Card testid="float-adjust">
            <div className="label-overline mb-3 flex items-center gap-2">
              <WalletIcon className="h-3.5 w-3.5"/>Float wallet
            </div>
            <div className="grid gap-3 sm:grid-cols-[1fr,2fr,auto]">
              <Field label="Amount (credits)" hint="Positive credits, negative claws back">
                <Input type="number" value={floatAmt}
                        onChange={(e) => setFloatAmt(e.target.value)}
                        data-testid="float-amount"/>
              </Field>
              <Field label="Reason / note">
                <Input value={floatNote} onChange={(e) => setFloatNote(e.target.value)}
                        placeholder="Monthly float top-up, promo credit, refund..."/>
              </Field>
              <Btn onClick={adjustFloat} disabled={busy} className="h-10 self-end"
                   data-testid="float-submit">
                {Number(floatAmt) < 0
                  ? (<><Minus className="h-4 w-4"/>Claw back</>)
                  : (<><Plus className="h-4 w-4"/>Credit float</>)}
              </Btn>
            </div>
          </Card>

          {/* Default commission */}
          <Card testid="default-commission-card">
            <div className="label-overline mb-3 flex items-center gap-2">
              <Percent className="h-3.5 w-3.5"/>Default commission
            </div>
            <div className="grid items-end gap-3 sm:grid-cols-[1fr,auto]">
              <Field label="Commission rate" hint="Fraction of each client send paid from admin margin. 0.15 = 15%.">
                <Input type="number" step="0.01" min="0" max="1"
                       value={defaultRate}
                       onChange={(e) => setDefaultRate(e.target.value)}
                       data-testid="default-rate-input"/>
              </Field>
              <Btn onClick={saveDefault} disabled={busy} className="h-10"
                   data-testid="default-rate-save">Save default</Btn>
            </div>
          </Card>

          {/* Per-country commission overrides */}
          <Card testid="overrides-card">
            <div className="label-overline mb-3 flex items-center gap-2">
              <Percent className="h-3.5 w-3.5"/>Country × channel overrides
            </div>

            <div className="grid gap-3 border border-zinc-900 bg-[#141414] p-3 sm:grid-cols-[1fr,1fr,1fr,auto]">
              <Field label="Country">
                <Select value={override.country}
                         onChange={(e) => setOverride({ ...override, country: e.target.value })}>
                  {COUNTRIES.map(c => <option key={c}>{c}</option>)}
                </Select>
              </Field>
              <Field label="Channel">
                <Select value={override.channel}
                         onChange={(e) => setOverride({ ...override, channel: e.target.value })}>
                  <option>sms</option><option>whatsapp</option>
                </Select>
              </Field>
              <Field label="Commission rate">
                <Input type="number" step="0.01" min="0" max="1"
                       value={override.commission_rate}
                       onChange={(e) => setOverride({ ...override, commission_rate: e.target.value })}
                       data-testid="override-rate"/>
              </Field>
              <Btn onClick={addOverride} disabled={busy} className="h-10 self-end"
                   data-testid="override-add">
                <Plus className="h-4 w-4"/>Add
              </Btn>
            </div>

            <div className="mt-3">
              <Table
                testid="overrides-table"
                rows={detail?.commission_overrides || []}
                empty="No overrides yet — default commission applies everywhere."
                columns={[
                  { key: "country", label: "Country", mono: true },
                  { key: "channel", label: "Channel", mono: true },
                  { key: "commission_rate", label: "Rate", mono: true, render: r => pct(r.commission_rate) },
                  { key: "active", label: "Status", render: r => <Pill status={r.active ? "active" : "down"}/> },
                  {
                    key: "del", label: "",
                    render: r => (
                      <button onClick={() => delOverride(r.id)}
                              className="text-zinc-400 hover:text-red-400"
                              data-testid={`override-del-${r.id}`}>✕</button>
                    ),
                  },
                ]}
              />
            </div>
          </Card>

          {/* Sub-clients */}
          <Card testid="clients-card">
            <div className="label-overline mb-3 flex items-center gap-2">
              <Users className="h-3.5 w-3.5"/>Sub-clients ({detail?.clients?.length || 0})
            </div>
            <Table
              testid="sub-clients-table"
              rows={detail?.clients || []}
              empty="No clients under this reseller yet."
              columns={[
                { key: "email", label: "Email", mono: true },
                { key: "name", label: "Name" },
                { key: "country", label: "Country", mono: true },
                { key: "status", label: "Status", render: r => <Pill status={r.status === "active" ? "active" : "down"}/> },
              ]}
            />
          </Card>
        </div>
      </div>
    </div>
  );
}

/* ------------------------- Reseller catalog tab ------------------------- */
function CatalogTab({ onOpen }) {
  const [rows, setRows] = useState([]);
  const [q, setQ] = useState("");
  const [loading, setLoading] = useState(false);

  const load = async () => {
    setLoading(true);
    try { const r = await http.get("/admin/resellers"); setRows(r.data); }
    catch { /* noop */ }
    finally { setLoading(false); }
  };
  useEffect(() => { load(); }, []);

  const filtered = useMemo(() => {
    const needle = q.trim().toLowerCase();
    if (!needle) return rows;
    return rows.filter(r => [r.email, r.name, r.business_name, r.reseller_code, r.country]
      .filter(Boolean).some(v => String(v).toLowerCase().includes(needle)));
  }, [rows, q]);

  const totals = useMemo(() => ({
    count: rows.length,
    float: rows.reduce((a, r) => a + (r.float_balance || 0), 0),
    clients: rows.reduce((a, r) => a + (r.clients_count || 0), 0),
    commission: rows.reduce((a, r) => a + (r.lifetime_commission || 0), 0),
  }), [rows]);

  return (
    <div className="space-y-4">
      <div className="grid grid-cols-2 gap-0 border border-zinc-900 sm:grid-cols-4">
        <Stat label="Resellers"            value={fmtNum(totals.count)} accent="white"/>
        <Stat label="Total float out"      value={`${fmtNum(totals.float)} cr`}/>
        <Stat label="Clients served"       value={fmtNum(totals.clients)}/>
        <Stat label="Commission paid (all time)" value={`${fmtNum(totals.commission)} cr`}/>
      </div>

      <div className="flex items-center justify-between border border-zinc-900 bg-[#141414] px-3 py-2">
        <div className="relative max-w-sm flex-1">
          <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500" strokeWidth={1.5}/>
          <Input
            placeholder="Search email, code, business..."
            value={q}
            onChange={(e) => setQ(e.target.value)}
            className="pl-9"
            data-testid="reseller-search"
          />
        </div>
        <Btn variant="ghost" onClick={load} disabled={loading}>
          <Activity className="h-4 w-4"/>{loading ? "Loading..." : "Refresh"}
        </Btn>
      </div>

      <Table
        testid="resellers-table"
        rows={filtered}
        empty="No resellers found."
        columns={[
          {
            key: "email",
            label: "Reseller",
            render: r => (
              <button onClick={() => onOpen(r)}
                      className="text-left hover:text-white"
                      data-testid={`open-reseller-${r.id}`}>
                <div className="text-sm font-medium text-white">{r.name || r.email}</div>
                <div className="font-mono text-[11px] text-zinc-500">{r.email}</div>
              </button>
            ),
          },
          { key: "reseller_code", label: "Code", mono: true },
          { key: "country", label: "Country", mono: true },
          { key: "commission_rate", label: "Commission", mono: true, render: r => pct(r.commission_rate) },
          { key: "float_balance", label: "Float", mono: true, render: r => `${fmtNum(r.float_balance)} cr` },
          { key: "clients_count", label: "Clients", mono: true, render: r => fmtNum(r.clients_count) },
          { key: "lifetime_commission", label: "Lifetime cmsn", mono: true, render: r => `${fmtNum(r.lifetime_commission)} cr` },
          { key: "status", label: "Status", render: r => <Pill status={r.status === "active" ? "active" : "down"}>{r.status}</Pill> },
        ]}
      />
    </div>
  );
}

/* ------------------------- Global policy tab ------------------------- */
function PolicyTab() {
  const [rows, setRows] = useState([]);
  const [draft, setDraft] = useState({});
  const [busy, setBusy] = useState(false);

  const load = () => http.get("/admin/settings").then(r => {
    setRows(r.data.filter(s => s.category === "reseller" || s.category === "pricing" ||
                                (s.key === "onboarding.reseller_signup_open")));
  }).catch(() => {});
  useEffect(() => { load(); }, []);

  const save = async (s) => {
    setBusy(true);
    try {
      const value = s.key in draft ? draft[s.key] : s.value;
      await http.put("/admin/settings", { key: s.key, value, category: s.category });
      toast.success(`Saved ${s.key}`);
      const nd = { ...draft }; delete nd[s.key]; setDraft(nd);
      load();
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
    finally { setBusy(false); }
  };

  const editor = (s) => {
    const v = s.key in draft ? draft[s.key] : s.value;
    const set = (val) => setDraft({ ...draft, [s.key]: val });
    if (typeof s.value === "boolean") {
      return (
        <Select value={v ? "1" : "0"} onChange={(e) => set(e.target.value === "1")}>
          <option value="1">enabled</option><option value="0">disabled</option>
        </Select>
      );
    }
    if (typeof s.value === "number") {
      return <Input type="number" step="0.01" value={v} onChange={(e) => set(Number(e.target.value))}/>;
    }
    return <Input value={v ?? ""} onChange={(e) => set(e.target.value)}/>;
  };

  return (
    <div className="space-y-3">
      <Card>
        <div className="flex items-start gap-3">
          <ShieldCheck className="mt-0.5 h-4 w-4 shrink-0 text-emerald-400" strokeWidth={1.5}/>
          <div className="text-sm text-zinc-400">
            Global policy that applies to every reseller unless overridden at the account level.
            Changes propagate instantly to signup forms, onboarding checks and commission defaults.
          </div>
        </div>
      </Card>

      {rows.length === 0 && (
        <div className="border border-dashed border-zinc-800 p-12 text-center text-sm text-zinc-500">
          Loading reseller policy settings...
        </div>
      )}

      {rows.map(s => {
        const isDraft = s.key in draft;
        return (
          <div key={s.key} className="grid gap-3 border border-zinc-900 bg-[#141414] p-4 sm:grid-cols-[1.2fr,1.5fr,auto] sm:items-end" data-testid={`policy-${s.key}`}>
            <div>
              <div className="font-mono text-[11px] uppercase tracking-widest text-zinc-500">{s.category}</div>
              <code className="mt-1 block font-mono text-sm text-white">{s.key}</code>
            </div>
            <Field label="Value">{editor(s)}</Field>
            <Btn onClick={() => save(s)} disabled={busy} className="h-10" data-testid={`policy-save-${s.key}`}>
              {isDraft ? "Save" : "Saved"}
            </Btn>
          </div>
        );
      })}
    </div>
  );
}

/* ------------------------- Commission audit tab ------------------------- */
function AuditTab() {
  const [days, setDays] = useState(30);
  const [data, setData] = useState({ transactions: [], by_reseller: [], grand_total: 0, since: "" });
  const [loading, setLoading] = useState(false);

  const load = async () => {
    setLoading(true);
    try {
      const r = await http.get(`/admin/resellers/commission-audit?days=${days}`);
      setData(r.data);
    } catch { /* noop */ }
    finally { setLoading(false); }
  };
  useEffect(() => { load(); /* eslint-disable-next-line */ }, [days]);

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between gap-3">
        <div className="flex items-center gap-3">
          <div className="label-overline">Window</div>
          <Select value={days} onChange={(e) => setDays(Number(e.target.value))} className="w-36" data-testid="audit-days">
            <option value={7}>Last 7 days</option>
            <option value={30}>Last 30 days</option>
            <option value={90}>Last 90 days</option>
            <option value={365}>Last year</option>
          </Select>
        </div>
        <Btn variant="ghost" onClick={load} disabled={loading}>
          <Activity className="h-4 w-4"/>{loading ? "Loading..." : "Refresh"}
        </Btn>
      </div>

      <div className="grid grid-cols-2 gap-0 border border-zinc-900 sm:grid-cols-3">
        <Stat label="Grand total" value={`${fmtNum(data.grand_total)} cr`} accent="white"/>
        <Stat label="Transactions" value={fmtNum(data.transactions.length)}/>
        <Stat label="Active resellers" value={fmtNum(data.by_reseller.length)}/>
      </div>

      <Card testid="by-reseller">
        <div className="label-overline mb-3">By reseller</div>
        <Table
          testid="by-reseller-table"
          rows={data.by_reseller}
          empty="No commissions paid in this window."
          rowKey="reseller_id"
          columns={[
            { key: "email", label: "Reseller", render: r => <span className="text-white">{r.name || r.email}</span> },
            { key: "email2", label: "Email", mono: true, render: r => <span className="text-zinc-500">{r.email}</span> },
            { key: "total_credits", label: "Total credits", mono: true, render: r => `${fmtNum(r.total_credits)} cr` },
          ]}
        />
      </Card>

      <Card testid="txs">
        <div className="label-overline mb-3">Recent transactions</div>
        <Table
          testid="txs-table"
          rows={data.transactions.slice(0, 200)}
          empty="No transactions."
          columns={[
            { key: "created_at", label: "When", mono: true, render: r => new Date(r.created_at).toLocaleString() },
            { key: "reseller_email", label: "Reseller", mono: true },
            { key: "amount", label: "Credits", mono: true, render: r => `+${fmtNum(r.amount)}` },
            { key: "note", label: "Note" },
            { key: "ref", label: "Campaign", mono: true, render: r => (r.ref || "").slice(0, 8) },
          ]}
        />
      </Card>
    </div>
  );
}

/* ------------------------- Main page ------------------------- */
export default function AdminResellers() {
  const [tab, setTab] = useState("catalog");
  const [selected, setSelected] = useState(null);

  return (
    <div>
      <PageHeader
        overline="Distribution"
        title="Resellers"
        desc="All reseller commercial controls in one place — catalog, per-account commission, float top-up, global policy and commission audit."
      />

      <div className="mb-4 flex flex-wrap gap-2 border-b border-zinc-900 pb-3" data-testid="reseller-tabs">
        {TABS.map(t => (
          <button
            key={t.key}
            onClick={() => setTab(t.key)}
            data-testid={`tab-${t.key}`}
            className={`inline-flex items-center gap-2 border px-4 py-2 text-sm transition-all ${
              tab === t.key
                ? "border-white bg-white text-black"
                : "border-zinc-900 bg-[#0e0e0e] text-zinc-400 hover:border-zinc-700 hover:text-white"
            }`}
          >
            <t.icon className="h-4 w-4" strokeWidth={1.5}/>
            {t.label}
          </button>
        ))}
      </div>

      {tab === "catalog" && <CatalogTab onOpen={setSelected}/>}
      {tab === "policy"  && <PolicyTab/>}
      {tab === "audit"   && <AuditTab/>}

      <ResellerDrawer
        reseller={selected}
        onClose={() => setSelected(null)}
        onMutate={() => { /* optionally refresh catalog */ }}
      />
    </div>
  );
}
