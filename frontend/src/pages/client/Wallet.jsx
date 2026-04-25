import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr, creditsShort, money, shortDate } from "@/lib/api";
import { useAuth } from "@/context/AuthContext";
import { PageHeader, Card, Field, Input, TextArea, Btn, Table, Pill, Modal } from "@/components/UI";
import {
  Sparkles, ArrowRight, RefreshCw, Building2, Upload,
  CheckCircle2, Clock, ChevronDown, Wallet as WalletIcon, Copy as CopyIcon,
} from "lucide-react";

export default function Wallet() {
  const { user, wallet, refresh } = useAuth();
  const [packs, setPacks] = useState([]);
  const [tx, setTx] = useState([]);
  const [rates, setRates] = useState(null);
  const [promo, setPromo] = useState("");
  const [busy, setBusy] = useState(null);
  const [banks, setBanks] = useState([]);
  const [topups, setTopups] = useState([]);
  const [payFor, setPayFor] = useState(null);
  const [openSection, setOpenSection] = useState({ topups: true, activity: true });

  const load = async () => {
    try {
      const [p, t, r, b, tu] = await Promise.all([
        http.get("/credits/packs"),
        http.get("/wallet/transactions"),
        http.get("/credits/rates"),
        http.get("/banks").catch(() => ({ data: [] })),
        http.get("/wallet/topups").catch(() => ({ data: [] })),
      ]);
      setPacks(p.data); setTx(t.data); setRates(r.data);
      setBanks(b.data || []); setTopups(tu.data || []);
    } catch { /* noop */ }
  };
  useEffect(() => { load(); }, []);

  const buy = async (pack) => {
    setBusy(pack.id);
    try {
      const { data } = await http.post("/credits/buy", { pack_id: pack.id, promo_code: promo || null });
      toast.success(`+${data.credits_added.toLocaleString()} credits!` + (data.bonus ? ` · bonus +${data.bonus}` : ""));
      setPromo(""); await refresh(); await load();
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
    finally { setBusy(null); }
  };

  const recover = async () => {
    try {
      await http.post("/credits/recover");
      toast.success("Account restored");
      await refresh();
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
  };

  const inactive = user?.status === "inactive";

  return (
    <div>
      <PageHeader
        overline="Treasury"
        title="Credits"
        desc="Top up, send messages, watch your balance work for you."
      />

      {inactive && (
        <Card className="mb-6 border-amber-500/30 bg-amber-500/5" testid="recover-banner">
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <div className="label-overline text-amber-400">Account inactive</div>
              <h3 className="mt-1 font-display text-lg font-semibold">Welcome back — let's reactivate.</h3>
              <p className="mt-1 text-sm text-zinc-400">
                Spend {rates?.inactivity_recovery_cost || 0} credits to restore full access.
              </p>
            </div>
            <Btn onClick={recover} data-testid="recover-btn" className="self-start sm:self-auto">
              <RefreshCw className="h-4 w-4" />Recover account
            </Btn>
          </div>
        </Card>
      )}

      {/* BALANCE + RATES — stacks on mobile, side-by-side on desktop */}
      <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr),minmax(0,1.6fr)]">
        <Card testid="credits-balance" className="overflow-hidden">
          <div className="flex items-center gap-2 text-blue-400">
            <WalletIcon className="h-4 w-4" strokeWidth={1.8} />
            <span className="text-xs font-semibold uppercase tracking-wider">Your balance</span>
          </div>
          <div
            className="mt-3 font-mono text-5xl font-medium leading-none tracking-tight text-zinc-100 sm:text-6xl"
            data-testid="credits-value"
          >
            {creditsShort(wallet?.balance)}
          </div>
          <div className="mt-2 text-[11px] uppercase tracking-wider text-zinc-500">credits available</div>

          {rates && (
            <div className="mt-5 grid grid-cols-2 gap-x-4 gap-y-2 border-t border-zinc-800/80 pt-4 text-xs sm:grid-cols-2">
              <RateRow label={`SMS in ${user?.country || "TZ"}`} value={`${rates.country_rate?.[user?.country] || rates.default_rate || 1} cr`} />
              <RateRow label="WhatsApp"         value={`${rates.whatsapp_rate} cr`} />
              <RateRow label="Sender ID (1yr)"  value={`${rates.sender_id_cost} cr`} />
              <RateRow label="Renewal"          value={`${rates.sender_id_renewal} cr`} />
            </div>
          )}
        </Card>

        {/* PACKS */}
        <div className="min-w-0">
          <div className="mb-3 flex flex-wrap items-end justify-between gap-3">
            <div>
              <div className="label-overline">Credit packs</div>
              <h3 className="mt-1 font-display text-lg font-semibold tracking-tight">Pick a pack, hit send.</h3>
            </div>
            <Input
              value={promo}
              onChange={(e) => setPromo(e.target.value)}
              placeholder="Promo code"
              className="h-9 w-full sm:w-44"
              data-testid="pack-promo"
            />
          </div>
          <div className="grid gap-3 sm:grid-cols-2">
            {packs.map((p, i) => (
              <PackCard
                key={p.id}
                pack={p}
                tagAccent={i === 1 ? "approved" : i === 2 ? "info" : "pending"}
                onPayBank={banks.length > 0 ? () => setPayFor(p) : null}
                onBuy={() => buy(p)}
                busy={busy === p.id}
              />
            ))}
          </div>
        </div>
      </div>

      {/* TOP-UPS — collapsible on mobile */}
      <CollapsibleSection
        title="My top-up requests"
        sub={`${topups.length} request${topups.length === 1 ? "" : "s"}`}
        open={openSection.topups}
        onToggle={() => setOpenSection((s) => ({ ...s, topups: !s.topups }))}
        testid="topups-section"
      >
        {topups.length === 0 ? (
          <div className="rounded-lg border border-dashed border-zinc-800 px-6 py-8 text-center text-sm text-zinc-500">
            No bank-transfer top-ups yet. Use "Pay by bank transfer" on any pack above.
          </div>
        ) : (
          <>
            {/* Desktop table */}
            <div className="hidden md:block">
              <Table testid="topups-table" rows={topups} columns={[
                { key: "created_at", label: "When", mono: true, render: r => shortDate(r.created_at) },
                { key: "pack_name",  label: "Pack", render: r => r.pack_name || "Custom" },
                { key: "local_amount", label: "Amount", mono: true,
                  render: r => `${r.local_currency} ${Number(r.local_amount).toLocaleString()}` },
                { key: "credits_on_approval", label: "Credits", mono: true,
                  render: r => Number(r.credits_on_approval).toLocaleString() },
                { key: "reference", label: "Ref", mono: true, render: r => r.reference || "—" },
                { key: "status", label: "Status", render: r => <TopupStatus s={r.status} /> },
                { key: "review_note", label: "Note", render: r => r.review_note || "—" },
              ]}/>
            </div>
            {/* Mobile card list */}
            <div className="space-y-2 md:hidden">
              {topups.map(r => (
                <div key={r.id} className="rounded-lg border border-zinc-800/80 bg-[#14171C] p-4">
                  <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                      <div className="text-sm font-medium text-zinc-100">
                        {r.pack_name || "Custom amount"}
                      </div>
                      <div className="mt-0.5 text-[11px] text-zinc-500">{shortDate(r.created_at)}</div>
                    </div>
                    <TopupStatus s={r.status} />
                  </div>
                  <div className="mt-3 grid grid-cols-2 gap-3 text-xs">
                    <KV k="Amount" v={`${r.local_currency} ${Number(r.local_amount).toLocaleString()}`} mono />
                    <KV k="Credits on approval" v={Number(r.credits_on_approval).toLocaleString()} mono />
                    {r.reference && <KV k="Reference" v={r.reference} mono />}
                    {r.review_note && <KV k="Admin note" v={r.review_note} />}
                  </div>
                </div>
              ))}
            </div>
          </>
        )}
      </CollapsibleSection>

      {/* ACTIVITY — collapsible on mobile */}
      <CollapsibleSection
        title="Activity"
        sub={`${tx.length} transaction${tx.length === 1 ? "" : "s"}`}
        open={openSection.activity}
        onToggle={() => setOpenSection((s) => ({ ...s, activity: !s.activity }))}
        testid="activity-section"
      >
        {/* Desktop table */}
        <div className="hidden md:block">
          <Table testid="tx-table" rows={tx} empty="No activity yet." columns={[
            { key: "created_at", label: "When", mono: true, render: r => shortDate(r.created_at) },
            { key: "kind", label: "Kind", render: r => <Pill status={r.amount >= 0 ? "success" : "info"}>{r.kind}</Pill> },
            { key: "note", label: "Note" },
            { key: "amount", label: "Credits", mono: true,
              render: r => <span className={r.amount >= 0 ? "text-emerald-400" : "text-zinc-300"}>
                {r.amount >= 0 ? "+" : ""}{Number(r.amount).toLocaleString()}
              </span> },
            { key: "balance_after", label: "Balance", mono: true,
              render: r => Number(r.balance_after).toLocaleString() },
          ]}/>
        </div>
        {/* Mobile card list */}
        <div className="space-y-2 md:hidden">
          {tx.length === 0 && (
            <div className="rounded-lg border border-dashed border-zinc-800 px-6 py-8 text-center text-sm text-zinc-500">
              No activity yet.
            </div>
          )}
          {tx.slice(0, 30).map(r => (
            <div key={r.id} className="flex items-start justify-between gap-3 rounded-lg border border-zinc-800/80 bg-[#14171C] p-3">
              <div className="min-w-0 flex-1">
                <div className="flex items-center gap-2">
                  <Pill status={r.amount >= 0 ? "success" : "info"}>{r.kind}</Pill>
                </div>
                <div className="mt-1.5 truncate text-xs text-zinc-300">{r.note}</div>
                <div className="mt-1 text-[11px] text-zinc-500">{shortDate(r.created_at)}</div>
              </div>
              <div className="shrink-0 text-right">
                <div className={`font-mono text-sm font-medium ${r.amount >= 0 ? "text-emerald-400" : "text-zinc-300"}`}>
                  {r.amount >= 0 ? "+" : ""}{Number(r.amount).toLocaleString()}
                </div>
                <div className="font-mono text-[10px] text-zinc-500">
                  bal {Number(r.balance_after).toLocaleString()}
                </div>
              </div>
            </div>
          ))}
        </div>
      </CollapsibleSection>

      <BankPayModal
        pack={payFor} banks={banks}
        onClose={() => setPayFor(null)}
        onSubmitted={() => { setPayFor(null); load(); refresh(); }}
      />
    </div>
  );
}

/* ---------- Sub-components ---------- */

function PackCard({ pack: p, tagAccent, onBuy, onPayBank, busy }) {
  return (
    <div className="card-surface relative flex min-w-0 flex-col p-5" data-testid={`pack-${p.id}`}>
      {p.tag && <div className="absolute right-3 top-3"><Pill status={tagAccent}>{p.tag}</Pill></div>}
      <div className="font-display text-base font-semibold text-zinc-100">{p.name}</div>
      <div className="mt-3 font-mono text-3xl font-medium tracking-tight text-zinc-100">
        {Number(p.credits).toLocaleString()}
      </div>
      <div className="text-[11px] uppercase tracking-wider text-zinc-500">credits</div>
      <div className="mt-3 flex flex-wrap items-baseline gap-x-2">
        <span className="font-mono text-base font-medium text-zinc-100">
          {p.local_currency} {Number(p.local_price).toLocaleString()}
        </span>
        {p.local_currency !== "USD" && (
          <span className="font-mono text-[11px] text-zinc-500">≈ {money(p.price_usd)}</span>
        )}
      </div>
      <div className="mt-1 text-[11px] text-zinc-500">
        ${(p.price_usd / p.credits).toFixed(4)} / credit
      </div>
      <div className="mt-4 flex flex-col gap-2">
        <Btn onClick={onBuy} disabled={busy} className="w-full" data-testid={`buy-${p.id}`}>
          <Sparkles className="h-4 w-4" />
          {busy ? "Processing…" : "Buy with demo credits"}
          <ArrowRight className="h-3.5 w-3.5" />
        </Btn>
        {onPayBank && (
          <Btn variant="secondary" onClick={onPayBank} className="w-full" data-testid={`pay-bank-${p.id}`}>
            <Building2 className="h-4 w-4" /> Pay by bank transfer
          </Btn>
        )}
      </div>
    </div>
  );
}

function CollapsibleSection({ title, sub, open, onToggle, children, testid }) {
  return (
    <section className="mt-7" data-testid={testid}>
      <button
        type="button"
        onClick={onToggle}
        className="flex w-full items-center justify-between gap-3 border-b border-zinc-800/80 pb-2.5 text-left transition hover:text-blue-400 md:cursor-default md:hover:text-current"
      >
        <div>
          <h2 className="font-display text-base font-semibold text-zinc-100">{title}</h2>
          {sub && <div className="text-[11px] text-zinc-500">{sub}</div>}
        </div>
        <ChevronDown
          className={`h-4 w-4 shrink-0 text-zinc-500 transition-transform md:hidden ${open ? "rotate-180" : ""}`}
          strokeWidth={2}
        />
      </button>
      <div className={`mt-3 ${open ? "block" : "hidden md:block"}`}>{children}</div>
    </section>
  );
}

function RateRow({ label, value }) {
  return (
    <div className="flex items-center justify-between">
      <span className="text-zinc-500">{label}</span>
      <span className="font-mono text-zinc-200">{value}</span>
    </div>
  );
}

function KV({ k, v, mono }) {
  return (
    <div>
      <div className="text-[10px] uppercase tracking-wider text-zinc-500">{k}</div>
      <div className={`mt-0.5 break-words text-zinc-200 ${mono ? "font-mono" : ""}`}>{v}</div>
    </div>
  );
}

function TopupStatus({ s }) {
  const map = { pending: "pending", approved: "active", rejected: "down" };
  return (
    <span className="inline-flex items-center gap-1.5">
      {s === "approved" && <CheckCircle2 className="h-3.5 w-3.5 text-emerald-400" />}
      {s === "pending"  && <Clock className="h-3.5 w-3.5 text-amber-400" />}
      <Pill status={map[s]}>{s}</Pill>
    </span>
  );
}

/* ---------- Bank pay modal (unchanged behaviour) ---------- */
function BankPayModal({ pack, banks, onClose, onSubmitted }) {
  const [bankId, setBankId] = useState("");
  const [reference, setReference] = useState("");
  const [note, setNote] = useState("");
  const [proof, setProof] = useState(null);
  const [preview, setPreview] = useState(null);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (pack) { setBankId(banks[0]?.id || ""); setReference(""); setNote(""); setProof(null); setPreview(null); }
  }, [pack, banks]);

  if (!pack) return null;
  const bank = banks.find((b) => b.id === bankId);

  const onFile = async (e) => {
    const f = e.target.files?.[0];
    if (!f) return;
    if (f.size > 4 * 1024 * 1024) return toast.error("Image is too big — max 4MB.");
    const reader = new FileReader();
    reader.onload = () => { setProof(reader.result); setPreview(reader.result); };
    reader.readAsDataURL(f);
  };

  const submit = async () => {
    if (!bankId) return toast.error("Pick a bank account.");
    if (!proof)  return toast.error("Please attach a payment proof.");
    setBusy(true);
    try {
      await http.post("/wallet/topups", {
        pack_id: pack.id, method: "bank_transfer", bank_id: bankId,
        reference, note, proof_image: proof,
      });
      toast.success("Top-up submitted. You'll get a notification once admin verifies.");
      onSubmitted?.();
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
    finally { setBusy(false); }
  };

  const copy = (v) => {
    navigator.clipboard?.writeText(String(v));
    toast.success("Copied");
  };

  return (
    <Modal open={!!pack} onClose={onClose} title="Pay by bank transfer" testid="bank-pay-modal">
      <div className="space-y-4">
        <div className="rounded-lg border border-zinc-800/80 bg-zinc-950/40 p-4">
          <div className="flex items-start justify-between gap-3">
            <div>
              <div className="font-medium text-zinc-100">{pack.name}</div>
              <div className="text-xs text-zinc-500">{Number(pack.credits).toLocaleString()} credits</div>
            </div>
            <div className="text-right">
              <div className="font-mono text-base font-medium text-zinc-100">
                {pack.local_currency} {Number(pack.local_price).toLocaleString()}
              </div>
              {pack.local_currency !== "USD" && (
                <div className="font-mono text-[10px] text-zinc-500">
                  ≈ ${pack.price_usd} · locked at {pack.fx_rate_used}
                </div>
              )}
            </div>
          </div>
        </div>

        <Field label="Pay into">
          <select
            value={bankId} onChange={(e) => setBankId(e.target.value)}
            className="h-10 w-full rounded-md border border-zinc-800 bg-zinc-950/60 px-3 text-sm text-zinc-100 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
            data-testid="bank-select"
          >
            {banks.map((b) => (
              <option key={b.id} value={b.id}>
                {b.bank_name} · {b.account_number} ({b.currency || "USD"})
              </option>
            ))}
          </select>
        </Field>

        {bank && (
          <div className="rounded-lg border border-zinc-800/80 bg-[#14171C] p-4 text-sm">
            <div className="label-overline mb-2">Transfer to these details</div>
            <div className="grid gap-1.5">
              <DetailRow k="Bank"            v={bank.bank_name} />
              <DetailRow k="Account name"    v={bank.account_name} />
              <DetailRow k="Account number"  v={bank.account_number} onCopy={copy} />
              {bank.branch && <DetailRow k="Branch" v={bank.branch} />}
              {bank.swift  && <DetailRow k="SWIFT"  v={bank.swift} onCopy={copy} />}
              <DetailRow k="Amount" v={`${pack.local_currency} ${Number(pack.local_price).toLocaleString()}`} />
            </div>
            {bank.instructions && (
              <div className="mt-3 rounded border border-blue-500/15 bg-blue-500/5 p-2 text-xs text-blue-200/90">
                {bank.instructions}
              </div>
            )}
          </div>
        )}

        <Field label="Bank reference number" hint="Whatever reference your bank or mobile-money receipt shows.">
          <Input value={reference} onChange={(e) => setReference(e.target.value)}
                  placeholder="e.g. TX4839421" data-testid="topup-ref"/>
        </Field>

        <Field label="Payment proof" hint="Photo of receipt or screenshot of the transfer. Max 4MB.">
          <label className="block cursor-pointer rounded-md border border-dashed border-zinc-800 bg-zinc-950/40 p-4 text-center text-sm text-zinc-400 transition hover:border-blue-500/40 hover:text-zinc-200">
            {preview ? (
              <>
                <img src={preview} alt="proof" className="mx-auto max-h-40 rounded object-contain" />
                <div className="mt-2 text-xs">Tap to replace</div>
              </>
            ) : (
              <>
                <Upload className="mx-auto mb-2 h-6 w-6" strokeWidth={1.5} />
                Click to attach photo / screenshot
              </>
            )}
            <input type="file" accept="image/*" onChange={onFile} className="hidden" data-testid="topup-proof" />
          </label>
        </Field>

        <Field label="Note (optional)">
          <TextArea value={note} onChange={(e) => setNote(e.target.value)}
                    className="min-h-[60px]" placeholder="Anything the admin should know." />
        </Field>

        <Btn onClick={submit} disabled={busy} className="w-full" data-testid="topup-submit">
          {busy ? "Submitting…" : "Submit for verification"}
        </Btn>
        <p className="text-[11px] leading-relaxed text-zinc-500">
          Credits are added as soon as admin verifies your payment — usually within one business day.
          You'll get a notification either way.
        </p>
      </div>
    </Modal>
  );
}

function DetailRow({ k, v, onCopy }) {
  return (
    <div className="flex items-center justify-between gap-3 border-b border-zinc-800/60 py-1.5 last:border-b-0">
      <span className="text-[11px] uppercase tracking-wider text-zinc-500">{k}</span>
      <span className="flex items-center gap-2 font-mono text-sm text-zinc-100">
        {v}
        {onCopy && (
          <button onClick={() => onCopy(v)} className="rounded p-1 text-zinc-500 hover:bg-white/5 hover:text-zinc-200" data-testid={`copy-${k}`}>
            <CopyIcon className="h-3 w-3" />
          </button>
        )}
      </span>
    </div>
  );
}
