import axios from "axios";

export const BACKEND_URL = process.env.REACT_APP_BACKEND_URL;
export const API = `${BACKEND_URL}/api`;

const http = axios.create({
  baseURL: API,
  withCredentials: true,
  headers: { "Content-Type": "application/json" },
});

export default http;

export function fmtErr(detail) {
  if (detail == null) return "Something went wrong. Please try again.";
  if (typeof detail === "string") return detail;
  if (Array.isArray(detail))
    return detail
      .map((e) => (e && typeof e.msg === "string" ? e.msg : JSON.stringify(e)))
      .filter(Boolean)
      .join(" ");
  if (detail && typeof detail.msg === "string") return detail.msg;
  return String(detail);
}

export function money(n, cur = "USD") {
  const v = Number(n || 0);
  return `${cur === "USD" ? "$" : ""}${v.toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;
}

export function credits(n) {
  const v = Math.floor(Number(n || 0));
  return v.toLocaleString() + (v === 1 ? " credit" : " credits");
}

export function creditsShort(n) {
  const v = Math.floor(Number(n || 0));
  return v.toLocaleString();
}

export function num(n) {
  return Number(n || 0).toLocaleString();
}

export function shortDate(s) {
  if (!s) return "—";
  try {
    return new Date(s).toLocaleString(undefined, {
      month: "short",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
    });
  } catch {
    return s;
  }
}
