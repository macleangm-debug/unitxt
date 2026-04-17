import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr, money, shortDate } from "@/lib/api";
import { useAuth } from "@/context/AuthContext";
import { PageHeader, Card, Field, Input, Btn, Table, Pill } from "@/components/UI";
import { Plus, ArrowDownToLine, ArrowUpFromLine } from "lucide-react";

export default function Wallet() {
  const { wallet, refresh } = useAuth();
  const [tx, setTx] = useState([]);
  const [amount, setAmount] = useState(50);
  const [promo, setPromo] = useState("");
  const [busy, setBusy] = useState(false);

  const load = () => http.get("/wallet/transactions").then(r => setTx(r.data)).catch(()=>{});
  useEffect(load, []);

  const topup = async (e) => {
    e.preventDefault();
    setBusy(true);
    try {
      const { data } = await http.post("/wallet/topup", { amount: Number(amount), method: "stripe", note: promo || null });
      toast.success(`+${money(amount)} added` + (data.bonus ? ` · bonus +${money(data.bonus)}` : ""));
      setPromo(""); load(); await refresh();
    } catch(err){ toast.error(fmtErr(err.response?.data?.detail)); }
    finally { setBusy(false); }
  };

  return (
    <div>
      <PageHeader overline="Treasury" title="Wallet" desc="Top up, view your balance, audit every transaction."/>

      <div className="grid gap-4 lg:grid-cols-[1fr,1fr,1fr]">
        <Card testid="wallet-card">
          <div className="label-overline">Current balance</div>
          <div className="mt-3 font-mono text-5xl font-medium tracking-tight text-emerald-400" data-testid="wallet-balance">{money(wallet?.balance)}</div>
          <div className="mt-1 text-xs text-zinc-500">{wallet?.currency}</div>
          <div className="mt-6 grid grid-cols-3 gap-2">
            {[50,100,200].map(v => (
              <button key={v} type="button" onClick={()=>setAmount(v)} data-testid={`amt-${v}`}
                className={`h-10 border text-sm font-mono transition ${Number(amount)===v?"border-white bg-white text-black":"border-zinc-800 hover:border-zinc-600"}`}>
                ${v}
              </button>
            ))}
          </div>
        </Card>

        <Card testid="topup-card" className="lg:col-span-2">
          <div className="label-overline">Top up</div>
          <form onSubmit={topup} className="mt-4 grid gap-3 sm:grid-cols-[1fr,1fr,auto]">
            <Field label="Amount (USD)"><Input type="number" min={1} step="any" value={amount} onChange={(e)=>setAmount(e.target.value)} required data-testid="topup-amount"/></Field>
            <Field label="Promo code"><Input value={promo} onChange={(e)=>setPromo(e.target.value)} placeholder="WELCOME10 / BONUS25" data-testid="topup-promo"/></Field>
            <div className="flex items-end">
              <Btn type="submit" disabled={busy} className="h-10 w-full" data-testid="topup-btn"><Plus className="h-4 w-4"/>{busy?"Processing…":"Top up"}</Btn>
            </div>
          </form>
          <div className="mt-3 text-xs text-zinc-500">Try <span className="font-mono text-zinc-300">WELCOME10</span> for 10% bonus on $50+, or <span className="font-mono text-zinc-300">BONUS25</span> for $25 free on $200.</div>
        </Card>
      </div>

      <h2 className="label-overline mt-8 mb-3">Transactions</h2>
      <Table testid="tx-table" rows={tx} empty="No transactions yet." columns={[
        { key:"created_at", label:"When", mono:true, render: r => shortDate(r.created_at) },
        { key:"kind", label:"Kind", render: r => <Pill status={r.amount>=0?"success":"info"}>{r.kind}</Pill> },
        { key:"note", label:"Note" },
        { key:"amount", label:"Amount", mono:true, render: r => <span className={r.amount>=0?"text-emerald-400":"text-zinc-300"}>{r.amount>=0?"+":""}{money(r.amount)}</span> },
        { key:"balance_after", label:"Balance", mono:true, render: r => money(r.balance_after) },
      ]}/>
    </div>
  );
}
