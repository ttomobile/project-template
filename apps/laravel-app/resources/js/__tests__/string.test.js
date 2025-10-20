import { describe, expect, it } from 'vitest';
import { formatGreeting } from '../utils/string.js';

describe('formatGreeting', () => {
  it('returns a friendly message with the provided name', () => {
    expect(formatGreeting('Laravel')).toBe('Hello, Laravel!');
  });

  it('falls back to a default when no name is provided', () => {
    expect(formatGreeting('')).toBe('Hello, there!');
    expect(formatGreeting()).toBe('Hello, there!');
  });
});
