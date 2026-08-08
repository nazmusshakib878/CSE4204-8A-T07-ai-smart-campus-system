import { describe, expect, it } from 'vitest';
import api from './api';

describe('API client', () => {
  it('has a finite request timeout', () => {
    expect(api.defaults.timeout).toBeGreaterThan(0);
    expect(api.defaults.timeout).toBe(15000);
  });
});
