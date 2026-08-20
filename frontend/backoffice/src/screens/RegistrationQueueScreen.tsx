/**
 * Backoffice registration queue.
 * Lists Limited customers; approve → Active.
 * Reject: no API endpoint — blocked (shown as note only).
 */

import { useCallback, useEffect, useState } from 'react';
import {
  approveRegistration,
  listRegistrationQueue,
  type RegistrationQueueItemDto,
} from '../api/registrations';
import { NoticeBanner } from '../ui';

export type RegistrationQueueItem = { customerId:string; mobile:string; fullName:string|null; nationalCode:string; accessStatus:string; kimiaBound:boolean; createdAt:string; };
export type RegistrationQueueScreenProps = { token:string; items?:RegistrationQueueItem[]; loading?:boolean; onApprove?:(customerId:string)=>Promise<void>; };
function mapItem(d:RegistrationQueueItemDto):RegistrationQueueItem{return{customerId:d.customer_id,mobile:d.mobile,fullName:d.full_name,nationalCode:d.national_code,accessStatus:d.access_status,kimiaBound:d.kimia_bound,createdAt:d.created_at};}
export function RegistrationQueueScreen(props:RegistrationQueueScreenProps){
 const controlled=props.items!==undefined; const [loading,setLoading]=useState(!controlled); const [error,setError]=useState<string|null>(null); const [items,setItems]=useState<RegistrationQueueItem[]>(props.items??[]); const [busyId,setBusyId]=useState<string|null>(null); const [selectedId,setSelectedId]=useState<string|null>(null);
 const reload=useCallback(async()=>{if(controlled)return;setLoading(true);setError(null);const res=await listRegistrationQueue(props.token);if(!res.ok){setError(res.message||res.error||'خطا در بارگذاری صف');setItems([]);}else setItems((res.data.items??[]).map(mapItem));setLoading(false);},[controlled,props.token]);
 useEffect(()=>{if(controlled){setItems(props.items??[]);setLoading(!!props.loading);return;}void reload();},[controlled,props.items,props.loading,reload]);
 async function handleApprove(customerId:string){setBusyId(customerId);setError(null);try{if(props.onApprove)await props.onApprove(customerId);else{const res=await approveRegistration(props.token,customerId);if(!res.ok){setError(res.message||res.error||'تأیید ناموفق');return;}}if(!controlled)await reload();setSelectedId(null);}finally{setBusyId(null);}}
 const selected=items.find(i=>i.customerId===selectedId)??null;
 if(loading)return <div className="tal-screen" dir="rtl" lang="fa"><p className="tal-muted">در حال بارگذاری صف ثبت‌نام…</p></div>;
 return <div className="tal-screen tal-reg-queue" dir="rtl" lang="fa"><header className="tal-header"><NoticeBanner tone="info">پایلوت: تأیید ثبت‌نام دستی است — Live Kimia Create خاموش است.</NoticeBanner><h1>صف ثبت‌نام</h1><p className="tal-muted">مشتریان در انتظار تأیید (Limited → Active)</p></header>{error?<p className="error" role="alert">{error}</p>:null}{!controlled?<p style={{marginBottom:'0.75rem'}}><button type="button" className="bo-btn-ghost" onClick={()=>void reload()}>تازه‌سازی</button></p>:null}{items.length===0?<p className="tal-muted">صف خالی است</p>:<ul className="tal-list">{items.map(it=><li key={it.customerId} className="tal-list-item"><button type="button" className="bo-btn-ghost" style={{width:'100%',textAlign:'right',marginBottom:8}} onClick={()=>setSelectedId(id=>id===it.customerId?null:it.customerId)}><div className="tal-list-title">{it.fullName||'—'}</div><div className="tal-list-meta" dir="ltr">{it.mobile} · {it.accessStatus}</div></button>{selectedId===it.customerId?<div className="tal-list-meta" style={{marginBottom:8}}><div>شناسه: <span dir="ltr">{it.customerId}</span></div><div>کد ملی: <span dir="ltr">{it.nationalCode||'—'}</span></div><div>ایجاد: <span dir="ltr">{it.createdAt}</span></div><div>Kimia bound: {it.kimiaBound?'بله':'خیر'}</div><p className="tal-muted" style={{marginTop:8}}>رد درخواست: endpoint در API فعلی تعریف نشده — Blocked</p></div>:null}<button type="button" disabled={busyId===it.customerId} onClick={()=>void handleApprove(it.customerId)} style={{width:'100%',padding:'0.55rem',borderRadius:8,border:'none',background:'#2d6cdf',color:'#fff',cursor:'pointer'}}>{busyId===it.customerId?'…':'تأیید ثبت‌نام'}</button></li>)}</ul>}{selected&&selectedId?null:null}</div>;
}
