import { useCallback,useEffect,useState } from 'react';
import Layout from '../components/Layout';
import { EmptyState,LoadingState,ModalDialog,StatusAlert } from '../components/Feedback';
import { downloadNoticeAttachment,getNotices,markNoticeRead } from '../services/api';
const normalize=n=>({...n,date:(n.publish_date||n.created_at||'').slice(0,10),tags:[n.category||'Academic',n.audience||'All',n.target_department,n.target_role,n.target_semester].filter(Boolean)});
function NoticeInboxPage(){
 const [notices,setNotices]=useState([]);const [selected,setSelected]=useState(null);const [meta,setMeta]=useState(null);const [page,setPage]=useState(1);const [loading,setLoading]=useState(true);const [error,setError]=useState('');
 const fetchNotices=useCallback(async()=>{setLoading(true);setError('');try{const r=await getNotices(page);setNotices((r.data.data||[]).map(normalize));setMeta(r.data.meta||null);}catch(e){setError(e.message);}finally{setLoading(false);}},[page]);
 useEffect(()=>{fetchNotices();},[fetchNotices]);
 const open=async n=>{setSelected(n);if(!n.is_read){try{await markNoticeRead(n.id);setNotices(v=>v.map(x=>x.id===n.id?{...x,is_read:true}:x));window.dispatchEvent(new Event('notice-read-updated'));}catch(e){setError(e.message);}}};
 const download=async n=>{try{await downloadNoticeAttachment(n.id,n.attachment_name||'notice-attachment.pdf');}catch(e){setError(e.message);}};
 const unread=notices.filter(n=>!n.is_read).length;
 return <Layout title="Messages" subtitle="Database-backed notices delivered to your account">
  {error&&<StatusAlert variant="danger" message={error} actionLabel="Try again" onAction={fetchNotices}/>}
  <div className="d-flex justify-content-between mb-4"><span className="admin-total-count">{unread} unread on this page</span><span>{meta?.total||0} notices</span></div>
  {loading?<LoadingState message="Loading messages..."/>:notices.length?<div className="admin-notice-list">{notices.map(n=><article key={n.id} className={`admin-notice-card ${n.is_read?'':'border-primary'}`}><div className="min-w-0"><div className="d-flex gap-2 mb-2">{!n.is_read&&<span className="badge bg-primary">New</span>}{n.tags.map(t=><span key={t} className="course-pill course-pill-primary">{t}</span>)}</div><h4>{n.title}</h4><small>{n.date}{n.author?.name?` by ${n.author.name}`:''}</small>{n.expires_at&&<small className="d-block">Expires {n.expires_at.slice(0,10)}</small>}</div><div className="admin-notice-actions"><button className="btn btn-sm btn-link" onClick={()=>open(n)}>View</button>{n.attachment_url&&<button className="btn btn-sm btn-link" onClick={()=>download(n)}>Download</button>}</div></article>)}</div>:<EmptyState title="No messages" message="Current, non-expired notices will appear here."/>}
  {meta?.last_page>1&&<div className="d-flex justify-content-center gap-2 mt-4"><button className="btn btn-outline-primary" disabled={page<=1} onClick={()=>setPage(p=>p-1)}>Previous</button><span className="align-self-center">Page {page} of {meta.last_page}</span><button className="btn btn-outline-primary" disabled={page>=meta.last_page} onClick={()=>setPage(p=>p+1)}>Next</button></div>}
  <ModalDialog open={Boolean(selected)} title={selected?.title||'Message'} onClose={()=>setSelected(null)}>{selected&&<><p className="text-secondary">{selected.description}</p>{selected.attachment_url&&<button className="btn btn-outline-primary" onClick={()=>download(selected)}>Download {selected.attachment_name||'attachment'}</button>}<small className="d-block mt-3">Published {selected.date}</small><div className="modal-footer px-0 pb-0 mt-4"><button className="btn btn-primary" onClick={()=>setSelected(null)}>Close</button></div></>}</ModalDialog>
 </Layout>;
}
export default NoticeInboxPage;
