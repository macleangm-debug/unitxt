import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { toast } from "sonner";
import http, { money, num } from "@/lib/api";
import { useAuth } from "@/context/AuthContext";
import { PageHeader, Stat, Card, Btn } from "@/components/UI";
import { Users, DollarSign, Wallet as WalletIcon, Copy, ArrowUpRight } from "lucide-react";

export default function ResellerDashboard() {
  const { user, wallet } = useAuth();
  const [clients, setClients] = useState([]);
  const [earn, setEarn] = useState(null);
  const [code, setCode] = useState("");

  useEffect(() => {
    Promise.all([
      http.get("/reseller/clients"),
      http.get("/reseller/earnings"),
      http.get("/reseller/code"),
    ]).then(([c, e, k]) => { setClients(c.data); setEarn(e.data); setCode(k.data.code); }).catch(()=>{});
  }, []);

  const copy = () => { navigator.clipboard.writeText(code); toast.success("Reseller code copied"); };

  return (
    <div>
      <PageHeader overline="Reseller console" title={`Hello, ${user?.name?.split(" ")[0] || ""}`} desc="Float, clients, and commissions — your business at a glance."/>
      <div className="grid gap-4 md:grid-cols-4">
        <Stat label="Float balance" value={money(wallet?.balance)} sub={wallet?.currency} accent="green" testid="r-stat-float"/>
        <Stat label="Active clients" value={num(clients.length)} testid="r-stat-clients"/>
        <Stat label="Client spend" value={money(earn?.client_spend||0)} testid="r-stat-spend"/>
        <Stat label="Earned" value={money(earn?.earned||0)} accent="green" sub={`${Math.round((earn?.commission_rate||0)*100)}% commission`} testid="r-stat-earn"/>
      </div>

      <div className="mt-6 grid gap-4 lg:grid-cols-3">
        <Card className="lg:col-span-2" testid="r-clients">
          <div className="flex items-center justify-between">
            <h3 className="font-display text-lg font-semibold">Top clients</h3>
            <Link to="/reseller/clients" className="text-xs text-zinc-400 hover:text-white">Manage all →</Link>
          </div>
          <div className="mt-4 divide-y divide-zinc-900">
            {clients.length === 0 && <div className="py-10 text-center text-sm text-zinc-500">No clients yet. Share your reseller code below.</div>}
            {clients.slice(0,5).map(c => (
              <div key={c.id} className="flex items-center justify-between py-3">
                <div>
                  <div className="text-sm font-medium">{c.name}</div>
                  <div className="font-mono text-[11px] uppercase tracking-widest text-zinc-500">{c.email}</div>
                </div>
                <div className="font-mono text-sm">{money(c.wallet_balance)}</div>
              </div>
            ))}
          </div>
        </Card>

        <Card testid="r-code">
          <div className="label-overline">Your reseller code</div>
          <div className="mt-4 flex items-center justify-between border border-zinc-800 bg-zinc-950 p-4">
            <code className="font-mono text-2xl font-bold">{code || "—"}</code>
            <button onClick={copy} className="text-zinc-400 hover:text-white" data-testid="copy-code"><Copy className="h-4 w-4"/></button>
          </div>
          <p className="mt-3 text-sm text-zinc-500">Share this code with clients during signup. They'll be auto-attached to your account.</p>
          <Link to="/reseller/clients" className="mt-4 inline-flex items-center gap-2 text-sm text-white"><ArrowUpRight className="h-3.5 w-3.5"/> Open clients</Link>
        </Card>
      </div>
    </div>
  );
}
