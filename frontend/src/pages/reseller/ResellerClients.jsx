import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr, money } from "@/lib/api";
import { PageHeader, Card, Field, Input, Btn, Table, Modal, Pill } from "@/components/UI";
import { Send } from "lucide-react";

export default function ResellerClients() {
  const [items, setItems] = useState([]);
  const [open, setOpen] = useState(null); // client object
  const [amount, setAmount] = useState(20);
  const [note, setNote] = useState("");
  const load = () => http.get("/reseller/clients").then(r => setItems(r.data)).catch(()=>{});
  useEffect(load, []);

  const transfer = async (e) => {
    e.preventDefault();
    try {
      await http.post("/reseller/transfer", { target_user_id: open.id, amount: Number(amount), note });
      toast.success(`Transferred ${money(amount)} to ${open.name}`);
      setOpen(null); setAmount(20); setNote(""); load();
    } catch(err){ toast.error(fmtErr(err.response?.data?.detail)); }
  };

  return (
    <div>
      <PageHeader overline="Distribution" title="Clients" desc="Onboard clients via your reseller code, fund their wallets, manage their access."/>

      <Table testid="r-clients-table" rows={items} empty="No clients yet." columns={[
        { key:"name", label:"Client" },
        { key:"business_name", label:"Business" },
        { key:"email", label:"Email", mono:true },
        { key:"country", label:"Country", mono:true },
        { key:"status", label:"Status", render: r => <Pill status={r.status}/> },
        { key:"wallet_balance", label:"Balance", mono:true, render: r => money(r.wallet_balance) },
        { key:"actions", label:"", render: r => <Btn variant="secondary" onClick={()=>setOpen(r)} data-testid={`transfer-${r.id}`}><Send className="h-3.5 w-3.5"/>Transfer</Btn> },
      ]}/>

      <Modal open={!!open} onClose={()=>setOpen(null)} title={`Transfer credits → ${open?.name||""}`} testid="transfer-modal">
        {open && (
          <form onSubmit={transfer} className="space-y-3">
            <div className="border border-zinc-800 bg-zinc-950 p-3 text-xs">
              <div className="flex justify-between"><span className="text-zinc-500">Client balance</span><span className="font-mono">{money(open.wallet_balance)}</span></div>
              <div className="mt-1 flex justify-between"><span className="text-zinc-500">Email</span><span className="font-mono">{open.email}</span></div>
            </div>
            <Field label="Amount (USD)"><Input type="number" min={1} step="any" value={amount} onChange={(e)=>setAmount(e.target.value)} required data-testid="transfer-amount"/></Field>
            <Field label="Note (optional)"><Input value={note} onChange={(e)=>setNote(e.target.value)} data-testid="transfer-note"/></Field>
            <Btn type="submit" className="w-full" data-testid="transfer-submit">Send funds</Btn>
          </form>
        )}
      </Modal>
    </div>
  );
}
