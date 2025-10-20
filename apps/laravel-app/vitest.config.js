import { defineConfig } from 'vitest/config';

export default defineConfig({
  test: {
    environment: 'node',
    include: ['resources/js/**/*.test.js'],
    reporters: 'default',
    coverage: {
      provider: 'v8',
      reports: ['text', 'lcov'],
    },
  },
});
