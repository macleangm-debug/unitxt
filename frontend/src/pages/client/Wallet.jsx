import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr, credits, creditsShort, money, shortDate } from "@/lib/api";
import { useAuth } from "@/context/AuthContext";
import { PageHeader, Card, Field, Input, TextArea, Btn, Table, Pill, Modal } from "@/components/UI";
import { Sparkles, ArrowRight, RefreshCw, Building2, Upload, Image as ImageIcon, CheckCircle2, Clock } from "lucide-react";

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
    } catch(err) { toast.error(fmtErr(err.response?.data?.detail)); }
    finally { setBusy(null); }
  };

  const recover = async () => {
    try {
      await http.post("/credits/recover");
      toast.success("Account restored");
      await refresh();
    } catch(err) { toast.error(fmtErr(err.response?.data?.detail)); }
  };

  const inactive = user?.status === "inactive";

  return (
    <div>
      <PageHeader overline="Treasury" title="Credits"
        desc="Buy credits. Send messages. Watch the meter go."/>

      {inactive && (
        <Card className="mb-6 border-orange-500/40 bg-orange-500/5" testid="recover-banner">
          <div className="flex items-center justify-between gap-4">
            <div>
              <div className="label-overline text-orange-400">Account inactive</div>
              <h3 className="mt-1 font-display text-lg font-semibold">Welcome back — let's reactivate.</h3>
              <p className="mt-1 text-sm text-zinc-400">Spend {rates?.inactivity_recovery_cost || 0} credits to restore full access.</p>
            </div>
            <Btn onClick={recover} data-testid="recover-btn"><RefreshCw className="h-4 w-4"/>Recover account</Btn>
          </div>
        </Card>
      )}

      <div className="grid gap-4 lg:grid-cols-[420px,1fr]">
        <Card testid="credits-balance">
          <div className="label-overline">Your balance</div>
          <div className="mt-3 font-mono text-6xl font-medium leading-none tracking-tight text-emerald-400" data-testid="credits-value">
            {creditsShort(wallet?.balance)}
          </div>
          <div className="mt-2 font-mono text-[11px] uppercase tracking-[0.25em] text-zinc-500">credits available</div>
          <div className="mt-6 border-t border-zinc-900 pt-4 text-xs text-zinc-500">
            {rates && (
              <>
                <div className="flex justify-between"><span>TZ SMS rate</span><span className="font-mono text-white">{rates.country_rate?.TZ || 1} cr</span></div>
                <div className="mt-1.5 flex justify-between"><span>WhatsApp rate</span><span className="font-mono text-white">{rates.whatsapp_rate} cr</span></div>
                <div className="mt-1.5 flex justify-between"><span>Sender ID (1yr)</span><span className="font-mono text-white">{rates.sender_id_cost} cr</span></div>
                <div className="mt-1.5 flex justify-between"><span>Sender ID renewal</span><span className="font-mono text-white">{rates.sender_id_renewal} cr</span></div>
              </>
            )}
          </div>
        </Card>

        <div>
          <div className="mb-3 flex items-center justify-between">
            <div>
              <div className="label-overline">Credit packs</div>
              <h3 className="mt-1 font-display text-xl font-semibold tracking-tight">Choose a pack</h3>
            </div>
            <div className="flex items-center gap-2">
              <Input value={promo} onChange={(e)=>setPromo(e.target.value)} placeholder="Promo code" className="h-9 w-40" data-testid="pack-promo"/>
            </div>
          </div>
          <div className="grid gap-3 sm:grid-cols-2">
            {packs.map((p, i) => (
              <div key={p.id} className="card-surface relative p-5" data-testid={`pack-${p.id}`}>
                {p.tag && <div className="absolute right-3 top-3"><Pill status={i===1?"approved":i===2?"info":"pending"}>{p.tag}</Pill></div>}
                <div className="font-display text-lg font-semibold">{p.name}</div>
                <div className="mt-3 font-mono text-4xl font-medium tracking-tight">{Number(p.credits).toLocaleString()}</div>
                <div className="font-mono text-[11px] uppercase tracking-widest text-zinc-500">credits</div>
                <div className="mt-4 flex items-baseline gap-2">
                  <span className="font-mono text-xl font-medium text-white">
                    {p.local_currency} {Number(p.local_price).toLocaleString()}
                  </span>
                  {p.local_currency !== "USD" && (
                    <span className="font-mono text-[11px] text-zinc-500">
                      ≈ {money(p.price_usd)}
                    </span>
                  )}
                </div>
                <div className="mt-1 text-[11px] text-zinc-600">
                  ${(p.price_usd / p.credits).toFixed(4)} per credit
                </div>
                {banks.length > 0 && (
                  <Btn variant="ghost" onClick={() => setPayFor(p)}
                       className="mt-3 w-full" data-testid={`pay-bank-${p.id}`}>
                    <Building2 className="h-4 w-4"/>Pay by bank transfer
                  </Btn>
                )}
                <Btn onClick={()=>buy(p)} disabled={busy===p.id} className="mt-2 w-full" data-testid={`buy-${p.id}`}>
                  <Sparkles className="h-4 w-4"/>{busy===p.id?"Processing…":"Buy with demo credits"} <ArrowRight className="h-3.5 w-3.5"/>
                </Btn>
              </div>
            ))}
          </div>
        </div>
      </div>

      <h2 className="label-overline mt-8 mb-3">My top-up requests</h2>
      {topups.length === 0 ? (
        <div className="border border-dashed border-zinc-800 p-6 text-center text-sm text-zinc-500">
          No pending bank-transfer top-ups. Use "Pay by bank transfer" on any pack above.
        </div>
      ) : (
        <Table testid="topups-table" rows={topups} columns={[
          { key: "created_at", label: "When", mono: true, render: r => shortDate(r.created_at) },
          { key: "pack_name",  label: "Pack", render: r => r.pack_name || "Custom amount" },
          { key: "local_amount", label: "Amount", mono: true,
            render: r => `${r.local_currency} ${Number(r.local_amount).toLocaleString()}` },
          { key: "credits_on_approval", label: "Credits on approval", mono: true,
            render: r => Number(r.credits_on_approval).toLocaleString() },
          { key: "reference", label: "Your reference", mono: true,
            render: r => r.reference || "—" },
          { key: "status", label: "Status", render: r => {
            const map = { pending: "pending", approved: "active", rejected: "down" };
            return <span className="inline-flex items-center gap-1">
              {r.status === "approved" ? <CheckCircle2 className="h-3 w-3 text-emerald-400"/>
                : r.status === "pending" ? <Clock className="h-3 w-3 text-amber-400"/>
                : null}
              <Pill status={map[r.status]}>{r.status}</Pill>
            </span>;
          } },
          { key: "review_note", label: "Admin note", render: r => r.review_note || "—" },
        ]}/>
      )}

      <h2 className="label-overline mt-8 mb-3">Activity</h2>
      <Table testid="tx-table" rows={tx} empty="No activity yet." columns={[
        { key:"created_at", label:"When", mono:true, render: r => shortDate(r.created_at) },
        { key:"kind", label:"Kind", render: r => <Pill status={r.amount>=0?"success":"info"}>{r.kind}</Pill> },
        { key:"note", label:"Note" },
        { key:"amount", label:"Credits", mono:true, render: r => <span className={r.amount>=0?"text-emerald-400":"text-zinc-300"}>{r.amount>=0?"+":""}{Number(r.amount).toLocaleString()}</span> },
        { key:"balance_after", label:"Balance", mono:true, render: r => Number(r.balance_after).toLocaleString() },
      ]}/>
      <BankPayModal
        pack={payFor} banks={banks}
        onClose={() => setPayFor(null)}
        onSubmitted={() => { setPayFor(null); load(); refresh(); }}
      />
    </div>
  );
}

function BankPayModal({ pack, banks, onClose, onSubmitted }) {
  const [bankId, setBankId] = useState("");
  const [reference, setReference] = useState("");
  const [note, setNote] = useState("");
  const [proof, setProof] = useState(null);  // base64
  const [preview, setPreview] = useState(null);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (pack) { setBankId(banks[0]?.id || ""); setReference(""); setNote(""); setProof(null); setPreview(null); }
  }, [pack, banks]);

  if (!pack) return null;
  const bank = banks.find(b => b.id === bankId);

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
    if (!proof) return toast.error("Please attach a payment proof (photo or PDF screenshot).");
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

  return (
    <Modal open={!!pack} onClose={onClose} title="Pay by bank transfer" testid="bank-pay-modal">
      <div className="space-y-4">
        <div className="border border-zinc-900 bg-[#0e0e0e] p-4 text-sm">
          <div className="flex items-center justify-between">
            <div>
              <div className="font-medium text-white">{pack.name}</div>
              <div className="text-xs text-zinc-500">{Number(pack.credits).toLocaleString()} credits</div>
            </div>
            <div className="text-right">
              <div className="font-mono text-lg text-white">{pack.local_currency} {Number(pack.local_price).toLocaleString()}</div>
              <div className="font-mono text-[11px] text-zinc-500">
                {pack.local_currency !== "USD" && `≈ $${pack.price_usd} · locked at ${pack.fx_rate_used}`}
              </div>
            </div>
          </div>
        </div>

        <Field label="Pay into">
          <select value={bankId} onChange={(e) => setBankId(e.target.value)}
                  className="w-full border border-zinc-800 bg-transparent px-3 py-2 text-sm text-white outline-none focus:border-white"
                  data-testid="bank-select">
            {banks.map(b => (
              <option key={b.id} value={b.id}>
                {b.bank_name} · {b.account_number} ({b.currency || "USD"})
              </option>
            ))}
          </select>
        </Field>

        {bank && (
          <div className="border border-zinc-900 bg-[#141414] p-4 text-sm">
            <div className="label-overline mb-2">Transfer to these details</div>
            <div className="grid gap-1">
              <Row k="Bank"           v={bank.bank_name}/>
              <Row k="Account name"   v={bank.account_name}/>
              <Row k="Account number" v={bank.account_number} copyable/>
              {bank.branch && <Row k="Branch" v={bank.branch}/>}
              {bank.swift  && <Row k="SWIFT"  v={bank.swift} copyable/>}
              <Row k="Amount" v={`${pack.local_currency} ${Number(pack.local_price).toLocaleString()}`}/>
            </div>
            {bank.instructions && (
              <div className="mt-3 text-xs text-zinc-400">{bank.instructions}</div>
            )}
          </div>
        )}

        <Field label="Bank reference number" hint="Whatever reference your bank / mobile money shows on the receipt.">
          <Input value={reference} onChange={(e) => setReference(e.target.value)}
                 placeholder="e.g. TX4839421" data-testid="topup-ref"/>
        </Field>

        <Field label="Payment proof" hint="Photo of receipt or screenshot of the transfer. Max 4MB.">
          <label className="block cursor-pointer border border-dashed border-zinc-800 bg-[#0e0e0e] p-4 text-center text-sm text-zinc-400 hover:border-zinc-600 hover:text-white">
            {preview ? (
              <>
                <img src={preview} alt="proof" className="mx-auto max-h-40 object-contain"/>
                <div className="mt-2 text-xs">Tap to pick a different image</div>
              </>
            ) : (
              <>
                <Upload className="mx-auto mb-2 h-6 w-6" strokeWidth={1.5}/>
                Click to attach photo / screenshot
              </>
            )}
            <input type="file" accept="image/*" onChange={onFile} className="hidden" data-testid="topup-proof"/>
          </label>
        </Field>

        <Field label="Note (optional)">
          <TextArea value={note} onChange={(e) => setNote(e.target.value)}
                    className="min-h-[60px]" placeholder="Anything the admin should know."/>
        </Field>

        <Btn onClick={submit} disabled={busy} className="w-full" data-testid="topup-submit">
          {busy ? "Submitting…" : "Submit for verification"}
        </Btn>
        <p className="text-[11px] text-zinc-500">
          Credits are added as soon as admin verifies your payment — usually within a business day.
          You'll get a notification either way.
        </p>
      </div>
    </Modal>
  );
}

function Row({ k, v, copyable }) {
  const copy = () => {
    navigator.clipboard?.writeText(String(v));
    toast.success("Copied");
  };
  return (
    <div className="flex items-center justify-between gap-3 border-b border-zinc-900 py-1.5">
      <span className="text-xs uppercase tracking-widest text-zinc-500">{k}</span>
      <span className="font-mono text-sm text-white">
        {v}
        {copyable && (
          <button onClick={copy} className="ml-2 text-[10px] text-zinc-500 hover:text-white"
                   data-testid={`copy-${k}`}>copy</button>
        )}
      </span>
    </div>
  );
}
