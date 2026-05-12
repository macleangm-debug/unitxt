import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { creditsShort } from "@/lib/api";
import { PageHeader, Card, Input, Btn } from "@/components/UI";
import { Copy, Share2, Gift, Users } from "lucide-react";

export default function Referrals() {
  const [data, setData] = useState(null);
  const [baseUrl, setBaseUrl] = useState(null);
  useEffect(() => {
    Promise.all([
      http.get("/referrals/me"),
      http.get("/public/branding"),
    ]).then(([r, b]) => {
      setData(r.data);
      setBaseUrl(b.data?.base_url || window.location.origin);
    }).catch(()=>{});
  }, []);
  if (!data) return <div className="font-mono text-xs text-zinc-500">LOADING…</div>;
  const link = `${(baseUrl || window.location.origin).replace(/\/$/, "")}/register?ref=${data.code}`;
  const copy = (s) => { navigator.clipboard.writeText(s); toast.success("Copied"); };

  return (
    <div>
      <PageHeader overline="Growth" title="Refer friends. Earn credits."
        desc={`Share your code. When friends buy a pack, you get ${data.percent_of_pack}% in bonus credits (up to ${data.max_per_referral} per referral).`}/>

      <div className="grid gap-4 lg:grid-cols-[1fr,1fr,1fr]">
        <Card testid="ref-code">
          <div className="label-overline">Your referral code</div>
          <div className="mt-4 flex items-center justify-between border border-zinc-800 bg-zinc-950 p-4">
            <code className="font-mono text-2xl font-bold">{data.code}</code>
            <button onClick={()=>copy(data.code)} className="text-zinc-400 hover:text-white" data-testid="copy-ref"><Copy className="h-4 w-4"/></button>
          </div>
          <div className="mt-4 label-overline">Share link</div>
          <div className="mt-2 flex gap-2">
            <Input readOnly value={link} data-testid="ref-link"/>
            <Btn onClick={()=>copy(link)} variant="secondary" data-testid="copy-link"><Share2 className="h-4 w-4"/></Btn>
          </div>
        </Card>

        <Card testid="ref-earned">
          <div className="label-overline">Bonus credits earned</div>
          <div className="mt-4 flex items-center gap-3">
            <Gift className="h-8 w-8 text-emerald-400"/>
            <div>
              <div className="font-mono text-4xl font-medium text-emerald-400">{creditsShort(data.earned)}</div>
              <div className="font-mono text-[10px] uppercase tracking-widest text-zinc-500">credits</div>
            </div>
          </div>
          <div className="mt-4 text-sm text-zinc-500">From {data.referred_count} referred friend{data.referred_count===1?"":"s"}.</div>
        </Card>

        <Card testid="ref-how">
          <div className="label-overline">How it works</div>
          <ol className="mt-4 space-y-3 text-sm text-zinc-400">
            <li className="flex gap-3"><span className="font-mono text-xs text-white">01</span> Share your code with a friend.</li>
            <li className="flex gap-3"><span className="font-mono text-xs text-white">02</span> They sign up using it.</li>
            <li className="flex gap-3"><span className="font-mono text-xs text-white">03</span> When they buy a pack, you earn {data.percent_of_pack}% in credits.</li>
            <li className="flex gap-3"><span className="font-mono text-xs text-white">04</span> Credits land in your wallet. No loss, no catch.</li>
          </ol>
          <div className="mt-4 flex items-center gap-2 border-t border-zinc-900 pt-3 text-xs text-zinc-500">
            <Users className="h-3.5 w-3.5"/> Loss-proof: rewards come from pack revenue, not your balance.
          </div>
        </Card>
      </div>
    </div>
  );
}
