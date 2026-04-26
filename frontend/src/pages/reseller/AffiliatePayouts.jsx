import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr, money, shortDate } from "@/lib/api";
import { PageHeader, Card, Field, Input, TextArea, Btn, Pill, Modal, Table } from "@/components/UI";
import { Wallet as WalletIcon, AlertCircle, Send } from "lucide-react";

const METHODS = [
  { key: "bank",         label: "Bank transfer" },
  { key: "mobile_money", label: "Mobile money (M-Pesa, Airtel Money, etc.)" },
  { key: "crypto",       label: "Crypto (USDT, USDC)" },
];

export default function AffiliatePayouts() {
  const [me, setMe] = useState(null);
  const [payouts, setPayouts] = useState([]);
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState({ method: "bank", details: {}, note: "" });
  const [busy, setBusy] = useState(false);

  const load = () => Promise.all([
    http.get("/affiliate/me"),
    http.get("/affiliate/payouts"),
  ]).then(([m, p]) => { setMe(m.data); setPayouts(p.data || []); }).catch(() => {});

  useEffect(() => { load(); }, []);

  const submit = async (e) => {
    e.preventDefault();
    if (!form.method) return toast.error("Pick a payout method.");
    setBusy(true);
    try {
      await http.post("/affiliate/payouts", {
        method: form.method,
        payout_details: form.details,
        note: form.note,
      });
      toast.success("Payout request submitted. We'll review within 1–2 business days.");
      setOpen(false); setForm({ method: "bank", details: {}, note: "" });
      load();
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
    finally { setBusy(false); }
  };

  if (!me) return <div className="text-zinc-500">Loading…</div>;
  const threshold = me.config?.payout_threshold_usd || 50;
  const canRequest = (me.earned_usd || 0) >= threshold;

  return (
    <div>
      <PageHeader
        overline="Affiliate workspace"
        title="Payouts"
        desc="Cash out your available earnings. We review within 1–2 business days, then pay via your chosen method."
      />

      {/* Hero — available + CTA */}
      <Card className="mb-5" testid="payout-hero">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <div className="label-overline flex items-center gap-1.5 text-blue-400">
              <WalletIcon className="h-3 w-3"/> Available to cash out
            </div>
            <div className={`mt-2 font-mono text-4xl font-medium tracking-tight ${canRequest ? "text-emerald-400" : "text-zinc-100"}`} data-testid="payout-available">
              {money(me.earned_usd)}
            </div>
            <div className="mt-1.5 text-xs text-zinc-500">
              {canRequest
                ? `Above the ${money(threshold)} minimum — ready to request a payout.`
                : `Need at least ${money(threshold)} to request a payout. Keep referring!`}
            </div>
          </div>
          <Btn
            onClick={() => setOpen(true)}
            disabled={!canRequest}
            data-testid="request-payout-btn"
          >
            <Send className="h-4 w-4"/>Request payout
          </Btn>
        </div>
      </Card>

      {/* History */}
      <h2 className="label-overline mb-2">Payout history</h2>

      {/* Desktop */}
      <div className="hidden md:block">
        <Table testid="payouts-table" rows={payouts} empty="No payouts yet." columns={[
          { key: "created_at", label: "Requested",   mono: true, render: r => shortDate(r.created_at) },
          { key: "amount_usd", label: "Amount",      mono: true, render: r => money(r.amount_usd) },
          { key: "method",     label: "Method" },
          { key: "status",     label: "Status",      render: r => <Pill status={r.status}>{r.status}</Pill> },
          { key: "review_note", label: "Note",       render: r => r.review_note || "—" },
        ]}/>
      </div>

      {/* Mobile */}
      <div className="space-y-2 md:hidden">
        {payouts.length === 0 && (
          <div className="rounded-lg border border-dashed border-zinc-800 px-6 py-8 text-center text-sm text-zinc-500">
            No payouts yet.
          </div>
        )}
        {payouts.map(p => (
          <div key={p.id} className="rounded-lg border border-zinc-800/80 bg-[#14171C] p-3" data-testid={`payout-${p.id}`}>
            <div className="flex items-start justify-between gap-3">
              <div className="min-w-0">
                <div className="font-mono text-sm font-medium text-zinc-100">{money(p.amount_usd)}</div>
                <div className="mt-0.5 text-[11px] text-zinc-500">{p.method} · {shortDate(p.created_at)}</div>
              </div>
              <Pill status={p.status}>{p.status}</Pill>
            </div>
            {p.review_note && <div className="mt-2 text-xs text-zinc-400">{p.review_note}</div>}
          </div>
        ))}
      </div>

      {/* Request modal */}
      <Modal open={open} onClose={() => setOpen(false)} title="Request payout" testid="payout-modal">
        <form onSubmit={submit} className="space-y-4">
          <div className="rounded-md border border-blue-500/20 bg-blue-500/[0.04] p-3 text-[12px] leading-relaxed text-blue-200/90">
            You're requesting <strong className="text-blue-300">{money(me.earned_usd)}</strong>.
            Pick how you'd like to be paid and provide the details.
          </div>

          <Field label="Payout method">
            <select
              value={form.method}
              onChange={(e) => setForm({ ...form, method: e.target.value, details: {} })}
              className="h-10 w-full rounded-md border border-zinc-800 bg-zinc-950/60 px-3 text-sm text-zinc-100 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
              data-testid="payout-method"
            >
              {METHODS.map(m => <option key={m.key} value={m.key}>{m.label}</option>)}
            </select>
          </Field>

          {/* Method-specific fields */}
          {form.method === "bank" && (
            <div className="grid gap-3">
              <Field label="Bank name">
                <Input value={form.details.bank_name || ""} onChange={(e) => setForm({ ...form, details: { ...form.details, bank_name: e.target.value }})} required data-testid="payout-bank-name"/>
              </Field>
              <Field label="Account name">
                <Input value={form.details.account_name || ""} onChange={(e) => setForm({ ...form, details: { ...form.details, account_name: e.target.value }})} required/>
              </Field>
              <Field label="Account number">
                <Input value={form.details.account_number || ""} onChange={(e) => setForm({ ...form, details: { ...form.details, account_number: e.target.value }})} required/>
              </Field>
              <Field label="SWIFT / BIC (international only)">
                <Input value={form.details.swift || ""} onChange={(e) => setForm({ ...form, details: { ...form.details, swift: e.target.value.toUpperCase() }})}/>
              </Field>
            </div>
          )}
          {form.method === "mobile_money" && (
            <div className="grid gap-3">
              <Field label="Provider" hint="e.g. M-Pesa, Airtel Money, Tigo Pesa">
                <Input value={form.details.provider || ""} onChange={(e) => setForm({ ...form, details: { ...form.details, provider: e.target.value }})} required data-testid="payout-mm-provider"/>
              </Field>
              <Field label="Phone number">
                <Input value={form.details.phone || ""} onChange={(e) => setForm({ ...form, details: { ...form.details, phone: e.target.value }})} required/>
              </Field>
              <Field label="Registered name">
                <Input value={form.details.name || ""} onChange={(e) => setForm({ ...form, details: { ...form.details, name: e.target.value }})} required/>
              </Field>
            </div>
          )}
          {form.method === "crypto" && (
            <div className="grid gap-3">
              <Field label="Network" hint="e.g. TRC-20, ERC-20, BEP-20">
                <Input value={form.details.network || ""} onChange={(e) => setForm({ ...form, details: { ...form.details, network: e.target.value.toUpperCase() }})} required data-testid="payout-crypto-network"/>
              </Field>
              <Field label="Wallet address">
                <Input value={form.details.address || ""} onChange={(e) => setForm({ ...form, details: { ...form.details, address: e.target.value }})} required/>
              </Field>
            </div>
          )}

          <Field label="Note (optional)">
            <TextArea value={form.note} onChange={(e) => setForm({ ...form, note: e.target.value })}
                      placeholder="Anything we should know."/>
          </Field>

          <div className="flex items-start gap-2 rounded-md border border-amber-500/20 bg-amber-500/[0.05] p-3 text-[11px] leading-relaxed text-amber-200/90">
            <AlertCircle className="mt-0.5 h-3.5 w-3.5 shrink-0"/>
            <span>Once you submit, your earned commissions move to "in review" until we approve and pay them.</span>
          </div>

          <div className="flex gap-2">
            <Btn variant="ghost" type="button" onClick={() => setOpen(false)} className="flex-1">Cancel</Btn>
            <Btn type="submit" disabled={busy} className="flex-1" data-testid="payout-submit">
              {busy ? "Submitting…" : "Submit request"}
            </Btn>
          </div>
        </form>
      </Modal>
    </div>
  );
}
