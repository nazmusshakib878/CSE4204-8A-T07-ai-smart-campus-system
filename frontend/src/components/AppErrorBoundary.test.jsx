import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import AppErrorBoundary from './AppErrorBoundary';

function BrokenPage() {
  throw new Error('test route error');
}

describe('AppErrorBoundary', () => {
  it('shows a recovery screen instead of a blank page when a route throws', () => {
    vi.spyOn(console, 'error').mockImplementation(() => {});
    render(<AppErrorBoundary><BrokenPage /></AppErrorBoundary>);

    expect(screen.getByRole('heading', { name: 'This page could not be loaded' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Reload page' })).toBeInTheDocument();
    console.error.mockRestore();
  });
});
