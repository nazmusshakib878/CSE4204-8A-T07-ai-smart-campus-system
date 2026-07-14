import { render,screen } from '@testing-library/react';
import { MemoryRouter,Route,Routes } from 'react-router-dom';
import { AuthContext } from './auth-context';
import RoleRoute from './RoleRoute';
const renderRoute=(role,path='/admin')=>render(<AuthContext.Provider value={{user:{role}}}><MemoryRouter initialEntries={[path]}><Routes><Route path="/dashboard" element={<div>Student home</div>}/><Route path="/faculty-dashboard" element={<div>Faculty home</div>}/><Route element={<RoleRoute allowedRoles={['admin']}/>}><Route path="/admin" element={<div>Admin content</div>}/></Route></Routes></MemoryRouter></AuthContext.Provider>);
describe('RoleRoute',()=>{it('renders an allowed role',()=>{renderRoute('admin');expect(screen.getByText('Admin content')).toBeInTheDocument();});it('redirects a student away from admin content',()=>{renderRoute('student');expect(screen.getByText('Student home')).toBeInTheDocument();expect(screen.queryByText('Admin content')).not.toBeInTheDocument();});it('redirects faculty to the faculty dashboard',()=>{renderRoute('faculty');expect(screen.getByText('Faculty home')).toBeInTheDocument();});});
