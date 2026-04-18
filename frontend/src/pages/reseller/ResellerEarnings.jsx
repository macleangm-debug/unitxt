import { useEffect, useState } from "react";
import http, { creditsShort } from "@/lib/api";
import { PageHeader, Stat, Card } from "@/components/UI";

export default function ResellerEarnings() {
  const [data, setData] = useState(null);
  useEffect(() => { http.get("/reseller/earnings").then(r => setData(r.data)).catch(()=>{}); }, []);
  return (
    <div>
      <PageHeader overline="Margins" title="Earnings" desc="Your commission on credits clients spend."/>
      <div className="grid gap-4 md:grid-cols-4">
        <Stat label="Active clients" value={data?.clients ?? "—"}/>
        <Stat label="Client credits spent" value={creditsShort(Math.abs(data?.client_spend||0))}/>
        <Stat label="Commission rate" value={`${Math.round((data?.commission_rate||0)*100)}%`}/>
        <Stat label="Credits earned" value={creditsShort(data?.earned||0)} accent="green"/>
      </div>
      <Card className="mt-6">
        <div className="label-overline">How earnings work</div>
        <p className="mt-3 max-w-2xl text-sm text-zinc-400">
          You earn a commission on every message your clients send. Top up their credits from your float,
          set your client pricing in Pricing, and watch your margin accrue here. Settlements run on the 1st
          of every month or on demand from the admin.
        </p>
      </Card>
    </div>
  );
}
