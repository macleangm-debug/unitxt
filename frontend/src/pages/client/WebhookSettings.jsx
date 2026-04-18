import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr } from "@/lib/api";
import { PageHeader, Card, Field, Input, Btn } from "@/components/UI";
import { Webhook, Save } from "lucide-react";

export default function WebhookSettings() {
  const [form, setForm] = useState({ dlr_webhook_url: "", dlr_webhook_secret: "" });
  useEffect(() => {
    http.get("/profile/webhook").then(r => setForm(r.data)).catch(()=>{});
  }, []);
  const save = async (e) => {
    e.preventDefault();
    try { await http.put("/profile/webhook", form); toast.success("Webhook saved"); }
    catch(err){ toast.error(fmtErr(err.response?.data?.detail)); }
  };
  return (
    <div>
      <PageHeader overline="Programmatic" title="Delivery webhooks"
        desc="We POST delivery statuses to your URL as messages are processed."/>
      <Card>
        <form onSubmit={save} className="grid gap-4 lg:grid-cols-2">
          <Field label="Webhook URL" hint="HTTPS recommended. We send JSON on every status update.">
            <Input type="url" value={form.dlr_webhook_url||""} onChange={(e)=>setForm({...form,dlr_webhook_url:e.target.value})} placeholder="https://your-app.com/hooks/unitxt" data-testid="webhook-url"/>
          </Field>
          <Field label="Shared secret" hint="Sent as X-unitxt-secret header. Use to verify requests.">
            <Input value={form.dlr_webhook_secret||""} onChange={(e)=>setForm({...form,dlr_webhook_secret:e.target.value})} placeholder="(optional)" data-testid="webhook-secret"/>
          </Field>
          <div className="lg:col-span-2">
            <Btn type="submit" data-testid="webhook-save"><Save className="h-4 w-4"/>Save webhook</Btn>
          </div>
        </form>
        <div className="mt-6 border-t border-zinc-900 pt-4">
          <div className="label-overline mb-2">Sample payload</div>
          <pre className="border border-zinc-800 bg-zinc-950 p-3 font-mono text-xs text-zinc-300">{`POST ${form.dlr_webhook_url||'https://your-app.com/hooks/unitxt'}
Content-Type: application/json
X-unitxt-secret: \${shared_secret}

{
  "id": "msg_...",
  "to": "+255712345678",
  "status": "delivered",
  "provider_msg_id": "tigo_abc123",
  "sender_id": "SUNRISE",
  "cost": 1,
  "channel": "sms",
  "at": "2026-04-18T00:00:00Z"
}`}</pre>
        </div>
      </Card>
    </div>
  );
}
