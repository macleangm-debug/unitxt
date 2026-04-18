import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr, credits, creditsShort, money, shortDate } from "@/lib/api";
import { useAuth } from "@/context/AuthContext";
import { PageHeader, Card, Field, Input, Btn, Table, Pill } from "@/components/UI";
import { Sparkles, ArrowRight, RefreshCw } from "lucide-react";

export default function Wallet() {
  const { user, wallet, refresh } = useAuth();
  const [packs, setPacks] = useState([]);
  const [tx, setTx] = useState([]);
  const [rates, setRates] = useState(null);
  const [promo, setPromo] = useState("");
  const [busy, setBusy] = useState(null);

  const load = async () => {
    try {
      const [p, t, r] = await Promise.all([
        http.get("/credits/packs"),
        http.get("/wallet/transactions"),
        http.get("/credits/rates"),
      ]);
      setPacks(p.data); setTx(t.data); setRates(r.data);
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
                <div className="mt-4 text-sm text-zinc-400">{money(p.price_usd)}<span className="ml-1 text-xs text-zinc-600">· ${(p.price_usd / p.credits).toFixed(4)}/cr</span></div>
                <Btn onClick={()=>buy(p)} disabled={busy===p.id} className="mt-4 w-full" data-testid={`buy-${p.id}`}>
                  <Sparkles className="h-4 w-4"/>{busy===p.id?"Processing…":"Buy now"} <ArrowRight className="h-3.5 w-3.5"/>
                </Btn>
              </div>
            ))}
          </div>
        </div>
      </div>

      <h2 className="label-overline mt-8 mb-3">Activity</h2>
      <Table testid="tx-table" rows={tx} empty="No activity yet." columns={[
        { key:"created_at", label:"When", mono:true, render: r => shortDate(r.created_at) },
        { key:"kind", label:"Kind", render: r => <Pill status={r.amount>=0?"success":"info"}>{r.kind}</Pill> },
        { key:"note", label:"Note" },
        { key:"amount", label:"Credits", mono:true, render: r => <span className={r.amount>=0?"text-emerald-400":"text-zinc-300"}>{r.amount>=0?"+":""}{Number(r.amount).toLocaleString()}</span> },
        { key:"balance_after", label:"Balance", mono:true, render: r => Number(r.balance_after).toLocaleString() },
      ]}/>
    </div>
  );
}
