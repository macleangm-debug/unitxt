import { useEffect, useState } from "react";
import http from "@/lib/api";
import { PageHeader, Card, Pill } from "@/components/UI";
import { ArrowRight, Globe2 } from "lucide-react";

export default function RoutingEngine() {
  const [providers, setProviders] = useState([]);
  const [countries, setCountries] = useState([]);
  useEffect(() => {
    Promise.all([http.get("/admin/providers"), http.get("/admin/countries")])
      .then(([p,c])=>{ setProviders(p.data); setCountries(c.data); }).catch(()=>{});
  }, []);

  const routesFor = (cc) => providers
    .filter(p => p.active && (p.countries?.includes(cc) || p.countries?.includes("*")))
    .sort((a,b)=>a.priority-b.priority);

  return (
    <div>
      <PageHeader overline="Topology" title="Routing engine" desc="For each country, the priority chain that decides which provider sends a message."/>
      <div className="grid gap-4 md:grid-cols-2">
        {countries.filter(c=>c.active).map(c => {
          const route = routesFor(c.code);
          return (
            <Card key={c.id} testid={`route-${c.code}`}>
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <div className="grid h-9 w-9 place-items-center border border-zinc-800 bg-zinc-950"><Globe2 className="h-4 w-4 text-zinc-400"/></div>
                  <div>
                    <div className="font-display text-base font-semibold">{c.name}</div>
                    <div className="font-mono text-[10px] uppercase tracking-widest text-zinc-500">{c.code} · {c.dial_code} · {c.currency}</div>
                  </div>
                </div>
                <Pill status={route.length?"healthy":"down"}>{route.length?`${route.length} hops`:"no route"}</Pill>
              </div>
              <div className="mt-4 flex flex-wrap items-center gap-2">
                {route.length === 0 && <span className="text-xs text-zinc-500">No active provider serves this country.</span>}
                {route.map((p, i) => (
                  <span key={p.id} className="flex items-center gap-2">
                    <span className="border border-zinc-800 bg-zinc-950 px-2.5 py-1 font-mono text-xs">
                      <span className="signal-dot mr-1.5 inline-block align-middle" style={{color:i===0?"#34C759":"#FF9F0A"}}/>
                      {p.name}
                    </span>
                    {i < route.length - 1 && <ArrowRight className="h-3.5 w-3.5 text-zinc-600"/>}
                  </span>
                ))}
              </div>
            </Card>
          );
        })}
      </div>
    </div>
  );
}
