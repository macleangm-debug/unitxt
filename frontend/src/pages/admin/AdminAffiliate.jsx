import { useEffect, useMemo, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr, money, shortDate } from "@/lib/api";
import { PageHeader, Card, Field, Input, Btn, Pill, Table, Modal } from "@/components/UI";
import {
  Sparkles, Settings as SettingsIcon, Users, DollarSign,
  CheckCircle2, AlertCircle, Send, Plus, Trash2,
} from "lucide-react";

const MODELS = [
  { key: "time_window", title: "Time window (default)",
    desc: "Earn % on every paid top-up made within N months of signup. After that, no commission." },
  { key: "first_n", title: "First N top-ups",
    desc: "Earn % on the user's first N paid top-ups. No time limit, predictable max payout." },
  { key: "tier_bonus", title: "Tier bonus (front-loaded)",
    desc: "Earn % on the first paid top-up + USD bonuses when the referee crosses spending tiers." },
];

export default function AdminAffiliate() {
  const [overview, setOverview] = useState(null);
  const [config, setConfig] = useState(null);
  const [busy, setBusy] = useState(false);
  const [payouts, setPayouts] = useState([]);
  const [reviewing, setReviewing] = useState(null);

  const load = () => Promise.all([
    http.get("/admin/affiliate/overview"),
    http.get("/admin/affiliate/payouts"),
  ]).then(([o, p]) => { setOverview(o.data); setConfig(o.data.config); setPayouts(p.data); }).catch(() => {});

  useEffect(() => { load(); }, []);

  const set = (k, v) => setConfig((c) => ({ ...c, [k]: v }));
  const num = (v) => (v === "" ? "" : Number(v));

  const save = async () => {
    setBusy(true);
    try {
      // Persist each affiliate.* key via Settings Hub
      const keys = Object.entries({
        "affiliate.active":                     config.active,
        "affiliate.model":                      config.model,
        "affiliate.commission_pct":             num(config.commission_pct),
        "affiliate.window_months":              num(config.window_months),
        "affiliate.first_n":                    num(config.first_n),
        "affiliate.tier_first_pct":             num(config.tier_first_pct),
        "affiliate.tier_bonus_1_threshold_usd": num(config.tier_bonus_1_threshold_usd),
        "affiliate.tier_bonus_1_amount_usd":    num(config.tier_bonus_1_amount_usd),
        "affiliate.tier_bonus_2_threshold_usd": num(config.tier_bonus_2_threshold_usd),
        "affiliate.tier_bonus_2_amount_usd":    num(config.tier_bonus_2_amount_usd),
        "affiliate.tier_window_months":         num(config.tier_window_months),
        "affiliate.welcome_bonus_pct":          num(config.welcome_bonus_pct),
        "affiliate.welcome_bonus_max_credits":  num(config.welcome_bonus_max_credits),
        "affiliate.payout_threshold_usd":       num(config.payout_threshold_usd),
      });
      await Promise.all(keys.map(([key, value]) =>
        http.put(`/admin/settings/${encodeURIComponent(key)}`, { value })
      ));
      toast.success("Affiliate program updated");
      await load();
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
    finally { setBusy(false); }
  };

  const review = async (status) => {
    if (!reviewing) return;
    try {
      await http.post(`/admin/affiliate/payouts/${reviewing.id}/review`, {
        status, note: reviewing.note || "",
      });
      toast.success(`Payout ${status}`);
      setReviewing(null); load();
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
  };

  if (!overview || !config) return <div className="text-zinc-500">Loading…</div>;

  return (
    <div>
      <PageHeader
        overline="Distribution & treasury"
        title="Affiliate program"
        desc="Promo-code-driven affiliate commissions. Affiliates share their codes; users get a small welcome bonus on first top-up; affiliates earn from every paid top-up using the model you choose below."
      />

      {/* OVERVIEW STRIP */}
      <div className="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <StatCard label="Affiliates"          value={overview.affiliates_count}        icon={Users}      testid="aff-stat-affiliates"/>
        <StatCard label="Referred users"      value={overview.referred_users_count}    icon={Send}       testid="aff-stat-referred"/>
        <StatCard label="Earned (lifetime)"   value={money((overview.earnings.earned?.usd || 0) + (overview.earnings.requested?.usd || 0) + (overview.earnings.paid?.usd || 0))} icon={DollarSign} testid="aff-stat-earned"/>
        <StatCard label="Pending payouts"     value={(overview.earnings.requested?.n || 0).toString()}   icon={AlertCircle} testid="aff-stat-pending"/>
      </div>

      {/* CONFIG */}
      <Card className="mb-5" testid="aff-config-card">
        <div className="flex items-start justify-between gap-3">
          <div>
            <div className="label-overline">Configuration</div>
            <h3 className="mt-1 font-display text-lg font-semibold">Commission model</h3>
            <p className="mt-1 max-w-2xl text-sm text-zinc-400">
              Pick how affiliates earn. You can change the model anytime —
              already-earned commissions are not retroactively recalculated.
            </p>
          </div>
          <label className="flex items-center gap-2 text-sm">
            <input
              type="checkbox" checked={!!config.active}
              onChange={(e) => set("active", e.target.checked)}
              data-testid="aff-active"
            />
            <span className="text-zinc-300">Program active</span>
          </label>
        </div>

        <div className="mt-5 grid gap-3 md:grid-cols-3">
          {MODELS.map((m) => (
            <button
              key={m.key}
              type="button"
              onClick={() => set("model", m.key)}
              data-testid={`aff-model-${m.key}`}
              className={`rounded-lg border p-4 text-left transition ${
                config.model === m.key
                  ? "border-blue-500/40 bg-blue-500/[0.06] ring-1 ring-blue-500/30"
                  : "border-zinc-800 bg-zinc-950/30 hover:border-zinc-700"
              }`}
            >
              <div className="flex items-center justify-between gap-2">
                <span className="text-sm font-semibold text-zinc-100">{m.title}</span>
                {config.model === m.key && <CheckCircle2 className="h-4 w-4 text-blue-400" />}
              </div>
              <p className="mt-2 text-xs leading-relaxed text-zinc-400">{m.desc}</p>
            </button>
          ))}
        </div>

        {/* MODEL-SPECIFIC FIELDS */}
        <div className="mt-6 rounded-md border border-zinc-800/80 bg-zinc-950/30 p-4">
          {config.model === "time_window" && (
            <div className="grid gap-3 sm:grid-cols-2">
              <Field label="Commission rate (%)" hint="Earned on every paid top-up within the window.">
                <Input type="number" step="0.5" min="0" max="100"
                        value={config.commission_pct}
                        onChange={(e) => set("commission_pct", e.target.value)}
                        data-testid="aff-commission-pct"/>
              </Field>
              <Field label="Window (months)" hint="After this many months from signup, commission stops.">
                <Input type="number" step="1" min="1" max="36"
                        value={config.window_months}
                        onChange={(e) => set("window_months", e.target.value)}
                        data-testid="aff-window-months"/>
              </Field>
            </div>
          )}
          {config.model === "first_n" && (
            <div className="grid gap-3 sm:grid-cols-2">
              <Field label="Commission rate (%)">
                <Input type="number" step="0.5" min="0" max="100"
                        value={config.commission_pct}
                        onChange={(e) => set("commission_pct", e.target.value)}/>
              </Field>
              <Field label="Apply to first N top-ups" hint="After the user makes N paid top-ups, commission stops.">
                <Input type="number" step="1" min="1" max="20"
                        value={config.first_n}
                        onChange={(e) => set("first_n", e.target.value)}
                        data-testid="aff-first-n"/>
              </Field>
            </div>
          )}
          {config.model === "tier_bonus" && (
            <div className="grid gap-3 sm:grid-cols-2">
              <Field label="First top-up commission (%)" hint="One-time, on the user's first paid top-up.">
                <Input type="number" step="0.5" min="0" max="100"
                        value={config.tier_first_pct}
                        onChange={(e) => set("tier_first_pct", e.target.value)}
                        data-testid="aff-tier-first-pct"/>
              </Field>
              <Field label="Tier window (months)" hint="Bonuses only count if hit within this window from signup.">
                <Input type="number" step="1" min="1" max="36"
                        value={config.tier_window_months}
                        onChange={(e) => set("tier_window_months", e.target.value)}/>
              </Field>
              <Field label="Tier 1 — referee spends ($)">
                <Input type="number" step="10" min="0" value={config.tier_bonus_1_threshold_usd}
                        onChange={(e) => set("tier_bonus_1_threshold_usd", e.target.value)}/>
              </Field>
              <Field label="Tier 1 — bonus paid ($)">
                <Input type="number" step="5" min="0" value={config.tier_bonus_1_amount_usd}
                        onChange={(e) => set("tier_bonus_1_amount_usd", e.target.value)}/>
              </Field>
              <Field label="Tier 2 — referee spends ($)">
                <Input type="number" step="10" min="0" value={config.tier_bonus_2_threshold_usd}
                        onChange={(e) => set("tier_bonus_2_threshold_usd", e.target.value)}/>
              </Field>
              <Field label="Tier 2 — bonus paid ($)">
                <Input type="number" step="5" min="0" value={config.tier_bonus_2_amount_usd}
                        onChange={(e) => set("tier_bonus_2_amount_usd", e.target.value)}/>
              </Field>
            </div>
          )}
        </div>
      </Card>

      <Card className="mb-5" testid="aff-bonus-card">
        <div className="label-overline">Welcome bonus to the referred user</div>
        <h3 className="mt-1 font-display text-base font-semibold">A small thank-you on their first top-up.</h3>
        <p className="mt-1 max-w-2xl text-sm text-zinc-400">
          Delivered as bonus credits on the referred user's first paid top-up.
          Capped so margins are protected.
        </p>
        <div className="mt-4 grid gap-3 sm:grid-cols-2">
          <Field label="Welcome bonus (%)" hint="% of the pack's credits added as bonus.">
            <Input type="number" step="0.5" min="0" max="50"
                    value={config.welcome_bonus_pct}
                    onChange={(e) => set("welcome_bonus_pct", e.target.value)}
                    data-testid="aff-welcome-pct"/>
          </Field>
          <Field label="Max bonus credits" hint="Hard cap regardless of pack size.">
            <Input type="number" step="50" min="0"
                    value={config.welcome_bonus_max_credits}
                    onChange={(e) => set("welcome_bonus_max_credits", e.target.value)}
                    data-testid="aff-welcome-cap"/>
          </Field>
        </div>
      </Card>

      <Card className="mb-5" testid="aff-payout-card">
        <div className="label-overline">Payouts</div>
        <h3 className="mt-1 font-display text-base font-semibold">Minimum threshold to request a payout.</h3>
        <div className="mt-3 grid gap-3 sm:grid-cols-2">
          <Field label="Min payout amount ($)">
            <Input type="number" step="10" min="0"
                    value={config.payout_threshold_usd}
                    onChange={(e) => set("payout_threshold_usd", e.target.value)}
                    data-testid="aff-payout-threshold"/>
          </Field>
        </div>
      </Card>

      <div className="mb-8 flex justify-end">
        <Btn onClick={save} disabled={busy} data-testid="aff-save">
          <SettingsIcon className="h-4 w-4"/>
          {busy ? "Saving…" : "Save affiliate settings"}
        </Btn>
      </div>

      {/* PAYOUTS */}
      <h2 className="label-overline mb-2">Payout queue</h2>
      <Table testid="aff-payouts-table" rows={payouts} empty="No payout requests yet." columns={[
        { key: "created_at", label: "When", mono: true, render: r => shortDate(r.created_at) },
        { key: "affiliate", label: "Affiliate", render: r => r.affiliate?.email || r.affiliate_id?.slice(0, 8) },
        { key: "amount_usd", label: "Amount", mono: true, render: r => money(r.amount_usd) },
        { key: "method", label: "Method" },
        { key: "status", label: "Status", render: r => <Pill status={r.status}>{r.status}</Pill> },
        { key: "actions", label: "",
          render: r => r.status === "pending" && (
            <button
              onClick={() => setReviewing({ ...r })}
              className="text-xs text-blue-400 hover:text-blue-300 underline-offset-2 hover:underline"
              data-testid={`review-payout-${r.id}`}
            >Review →</button>
          )},
      ]}/>

      <Modal open={!!reviewing} onClose={() => setReviewing(null)} title="Review payout" testid="aff-review-modal">
        {reviewing && (
          <div className="space-y-4">
            <div className="rounded-md border border-zinc-800/80 bg-zinc-950/40 p-4 text-sm">
              <div className="flex justify-between"><span className="text-zinc-500">Affiliate</span><span className="text-zinc-100">{reviewing.affiliate?.email}</span></div>
              <div className="mt-2 flex justify-between"><span className="text-zinc-500">Amount</span><span className="font-mono text-zinc-100">{money(reviewing.amount_usd)}</span></div>
              <div className="mt-2 flex justify-between"><span className="text-zinc-500">Method</span><span className="text-zinc-100">{reviewing.method}</span></div>
              {reviewing.payout_details && Object.keys(reviewing.payout_details).length > 0 && (
                <div className="mt-2 border-t border-zinc-800 pt-2">
                  <div className="text-zinc-500">Payout details</div>
                  <pre className="mt-1 whitespace-pre-wrap font-mono text-[11px] text-zinc-300">
{JSON.stringify(reviewing.payout_details, null, 2)}
                  </pre>
                </div>
              )}
              {reviewing.note && (
                <div className="mt-2"><span className="text-zinc-500">Note</span>: <span className="text-zinc-100">{reviewing.note}</span></div>
              )}
            </div>
            <Field label="Internal note (optional)">
              <Input value={reviewing.note_admin || ""}
                      onChange={(e) => setReviewing({ ...reviewing, note: e.target.value })}
                      placeholder="e.g. paid via M-Pesa, ref TX..."
                      data-testid="aff-review-note"/>
            </Field>
            <div className="flex gap-2">
              <Btn variant="danger" onClick={() => review("rejected")} className="flex-1" data-testid="aff-review-reject">Reject</Btn>
              <Btn onClick={() => review("approved")} className="flex-1" data-testid="aff-review-approve">Approve & mark paid</Btn>
            </div>
          </div>
        )}
      </Modal>
    </div>
  );
}

function StatCard({ label, value, icon: Icon, testid }) {
  return (
    <div className="card-surface p-4" data-testid={testid}>
      <div className="flex items-center gap-2 text-zinc-500">
        <Icon className="h-4 w-4 text-blue-400" strokeWidth={1.8} />
        <span className="text-[11px] font-semibold uppercase tracking-wider">{label}</span>
      </div>
      <div className="mt-2 font-mono text-xl font-medium text-zinc-100">{value}</div>
    </div>
  );
}
