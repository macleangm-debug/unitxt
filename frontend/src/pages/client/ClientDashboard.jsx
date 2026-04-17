import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import http, { money, num, shortDate } from "@/lib/api";
import { useAuth } from "@/context/AuthContext";
import { PageHeader, Stat, Card, Pill, Btn } from "@/components/UI";
import { Send, Upload, Wallet as WalletIcon, ArrowUpRight, MessageSquare } from "lucide-react";

export default function ClientDashboard() {
  const { user, wallet } = useAuth();
  const [stats, setStats] = useState(null);
  const [campaigns, setCampaigns] = useState([]);

  useEffect(() => {
    (async () => {
      try {
        const [s, c] = await Promise.all([
          http.get("/messaging/stats"),
          http.get("/messaging/campaigns?limit=5"),
        ]);
        setStats(s.data);
        setCampaigns(c.data);
      } catch { /* noop */ }
    })();
  }, []);

  return (
    <div>
      <PageHeader
        overline={`Workspace · ${user?.business_name || user?.name}`}
        title={`Hello, ${user?.name?.split(" ")[0] || "friend"}.`}
        desc="Your dispatch console. Quick send, bulk send, or schedule what comes next."
        testid="client-dashboard-header"
        actions={
          <>
            <Link to="/client/quick-send"><Btn data-testid="cta-quick-send"><Send className="h-4 w-4"/>Quick send</Btn></Link>
            <Link to="/client/bulk-send"><Btn variant="secondary" data-testid="cta-bulk-send"><Upload className="h-4 w-4"/>Bulk send</Btn></Link>
          </>
        }
      />

      <div className="grid gap-4 md:grid-cols-4">
        <Stat label="Wallet balance" value={money(wallet?.balance)} sub={wallet?.currency} accent="green" testid="stat-wallet"/>
        <Stat label="Messages sent" value={num(stats?.total_messages || 0)} testid="stat-msgs"/>
        <Stat label="Delivery rate" value={`${stats?.delivery_rate || 0}%`} sub={`${num(stats?.delivered || 0)} delivered`} accent="green" testid="stat-rate"/>
        <Stat label="Failed" value={num(stats?.failed || 0)} accent={stats?.failed ? "red" : "white"} testid="stat-failed"/>
      </div>

      <div className="mt-6 grid gap-4 lg:grid-cols-3">
        <Card className="lg:col-span-2" testid="recent-campaigns">
          <div className="flex items-center justify-between">
            <h3 className="font-display text-lg font-semibold">Recent campaigns</h3>
            <Link to="/client/campaigns" className="text-xs text-zinc-400 hover:text-white">View all →</Link>
          </div>
          <div className="mt-4 divide-y divide-zinc-900">
            {campaigns.length === 0 && <div className="py-10 text-center text-sm text-zinc-500">No campaigns yet. Send your first message →</div>}
            {campaigns.map((c) => (
              <div key={c.id} className="flex items-center justify-between py-3" data-testid={`campaign-row-${c.id}`}>
                <div>
                  <div className="text-sm font-medium">{c.name}</div>
                  <div className="font-mono text-[11px] uppercase tracking-widest text-zinc-500">
                    {c.channel} · {c.total} recipients · {shortDate(c.created_at)}
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <div className="font-mono text-xs text-zinc-400">{c.delivered}/{c.sent}</div>
                  <Pill status={c.status} />
                </div>
              </div>
            ))}
          </div>
        </Card>

        <Card testid="quick-tip">
          <div className="label-overline">Pro tip</div>
          <h3 className="mt-3 font-display text-xl font-semibold tracking-tight">Top up & save 10% with WELCOME10</h3>
          <p className="mt-2 text-sm text-zinc-500">Apply the promo code at top-up to claim a 10% bonus on $50+. Or unlock $25 free credit with BONUS25 on $200.</p>
          <Link to="/client/wallet" className="mt-4 inline-flex items-center gap-2 text-sm text-white underline-offset-4 hover:underline" data-testid="goto-wallet">
            <WalletIcon className="h-4 w-4"/> Open wallet <ArrowUpRight className="h-3.5 w-3.5"/>
          </Link>
          <div className="mt-6 border-t border-zinc-900 pt-4">
            <div className="label-overline">Channels available</div>
            <div className="mt-3 flex flex-wrap gap-2">
              <span className="pill pill-blue"><MessageSquare className="h-3 w-3"/> SMS</span>
              <span className="pill pill-green"><MessageSquare className="h-3 w-3"/> WhatsApp</span>
            </div>
          </div>
        </Card>
      </div>
    </div>
  );
}
