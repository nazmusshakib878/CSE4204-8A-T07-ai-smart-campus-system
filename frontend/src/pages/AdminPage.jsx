import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import Layout from '../components/Layout';
import { EmptyState, LoadingState, StatusAlert } from '../components/Feedback';
import { getAdminDashboard } from '../services/api';

const icons = {
  users: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m7-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm11 10v-2a3 3 0 0 0-2-2.83M16 3.13a4 4 0 0 1 0 7.75',
  students: 'M4 10 12 5l8 5-8 5-8-5Zm3 3.5V18c2.8 2 7.2 2 10 0v-4.5M20 10v6',
  faculty: 'M4 5h16v14H4V5Zm4 4h8M8 13h5',
  alert: 'M12 3 22 20H2L12 3Zm0 6v5m0 3h.01',
  arrow: 'm9 18 6-6-6-6',
};
function AdminIcon({ name }) { return <svg viewBox="0 0 24 24" aria-hidden="true"><path d={icons[name]} /></svg>; }
const relativeTime=(value)=>{if(!value)return '';const seconds=Math.max(0,Math.floor((Date.now()-new Date(value).getTime())/1000));if(seconds<60)return 'Just now';if(seconds<3600)return `${Math.floor(seconds/60)} min ago`;if(seconds<86400)return `${Math.floor(seconds/3600)} hr ago`;return `${Math.floor(seconds/86400)} day ago`;};

function AdminPage() {
 const [data,setData]=useState(null); const [loading,setLoading]=useState(true); const [error,setError]=useState('');
 useEffect(()=>{let active=true;getAdminDashboard().then(r=>{if(active)setData(r.data.data);}).catch(e=>{if(active)setError(e.message);}).finally(()=>{if(active)setLoading(false);});return()=>{active=false;};},[]);
 const stats=useMemo(()=>data?[
  {label:'Total Users',value:data.stats.total_users,detail:`${data.pending_actions.user_approvals} pending approval`,tone:'blue',icon:'users'},
  {label:'Active Students',value:data.stats.active_students,detail:'Approved student accounts',tone:'green',icon:'students'},
  {label:'Faculty Members',value:data.stats.faculty_members,detail:'Approved faculty accounts',tone:'amber',icon:'faculty'},
  {label:'Pending Actions',value:data.stats.pending_actions,detail:'Approvals and high-risk alerts',tone:'red',icon:'alert'},
 ]:[],[data]);
 const maxDepartment=Math.max(1,...(data?.departments||[]).flatMap(d=>[d.students,d.faculty]));
 return <Layout title="Admin Dashboard" subtitle="Northern University of Business and Technology | Khulna">
  <section className="admin-dashboard-hero mb-4"><div><span className="admin-dashboard-eyebrow">ADMIN CONTROL CENTER</span><h2>Campus overview</h2><p>Live operational data from the campus database.</p></div><div className="admin-live-status"><span/><div><strong>Database connected</strong><small>Dashboard refreshed on load</small></div></div></section>
  {error&&<StatusAlert variant="danger" message={error}/>}
  {loading?<LoadingState message="Loading admin dashboard..."/>:data&&<>
   <div className="row g-4 mb-4">{stats.map(i=><div key={i.label} className="col-sm-6 col-xl-3"><article className={`admin-kpi-card admin-kpi-${i.tone}`}><div className="admin-kpi-header"><span className="admin-kpi-icon"><AdminIcon name={i.icon}/></span></div><span className="admin-kpi-label">{i.label}</span><strong>{Number(i.value).toLocaleString()}</strong><small>{i.detail}</small></article></div>)}</div>
   <div className="row g-4 mb-4"><div className="col-xl-8"><section className="admin-dashboard-panel h-100"><div className="admin-panel-heading"><div><span className="admin-panel-eyebrow">CAMPUS INSIGHT</span><h3>User distribution</h3><p>Approved students and faculty by department</p></div><div className="admin-chart-legend"><span><i className="legend-student"/>Students</span><span><i className="legend-faculty"/>Faculty</span></div></div>
    {data.departments.length?<div className="admin-department-chart admin-chart-polished">{data.departments.map(d=><div key={d.label} className="bar-chart-group"><div className="bar-chart-bars admin-bars"><span style={{height:`${Math.max(8,d.students*100/maxDepartment)}%`}} title={`${d.students} students`}/><span style={{height:`${Math.max(8,d.faculty*100/maxDepartment)}%`}} title={`${d.faculty} faculty`}/></div><strong>{d.label}</strong></div>)}</div>:<EmptyState title="No department data" message="Approved users with departments will appear here."/>}
   </section></div><div className="col-xl-4"><section className="admin-dashboard-panel h-100"><div className="admin-panel-heading"><div><span className="admin-panel-eyebrow">REQUIRES ATTENTION</span><h3>Pending actions</h3></div><span className="admin-total-badge">{data.stats.pending_actions} total</span></div><div className="admin-action-list admin-action-list-polished"><div className="admin-action-item admin-action-amber"><span className="admin-action-dot"/><div><strong>User Approvals</strong><small>Accounts awaiting review</small></div><span className="admin-action-count">{data.pending_actions.user_approvals}</span></div><div className="admin-action-item admin-action-red"><span className="admin-action-dot"/><div><strong>Risk Alerts</strong><small>High-risk students</small></div><span className="admin-action-count">{data.pending_actions.risk_alerts}</span></div></div><Link to="/admin/users" className="btn btn-primary w-100 mt-4">Review pending users <AdminIcon name="arrow"/></Link></section></div></div>
   <section className="admin-quick-actions mb-4"><div className="admin-panel-heading mb-3"><div><span className="admin-panel-eyebrow">SHORTCUTS</span><h3>Quick management</h3></div></div><div className="row g-3">{[['/admin/users','Manage users','Approve accounts','users'],['/admin/notices','Manage notices','Publish announcements','faculty'],['/admin/departments','Departments','Maintain departments','students'],['/risk-alerts','Risk alerts','Review priority alerts','alert']].map(([to,title,copy,icon])=><div key={to} className="col-sm-6 col-xl-3"><Link to={to} className="admin-quick-link"><span className="admin-quick-icon"><AdminIcon name={icon}/></span><span><strong>{title}</strong><small>{copy}</small></span><AdminIcon name="arrow"/></Link></div>)}</div></section>
   <section className="admin-dashboard-panel"><div className="admin-panel-heading mb-4"><div><span className="admin-panel-eyebrow">AUDIT TRAIL</span><h3>Recent system activity</h3><p>Latest users, notices and risk alerts</p></div></div>{data.recent_activity.length?<div className="admin-activity-list admin-activity-timeline">{data.recent_activity.map((a,i)=><div key={`${a.type}-${a.occurred_at}-${i}`} className="admin-activity-item"><span className={`admin-activity-marker marker-${a.type==='risk'?'red':a.type==='notice'?'green':'blue'}`}/><div><strong>{a.title}</strong><span>{a.detail}</span></div><small>{relativeTime(a.occurred_at)}</small></div>)}</div>:<EmptyState title="No recent activity" message="New campus activity will appear here."/ >}</section>
  </>}
 </Layout>;
}
export default AdminPage;
