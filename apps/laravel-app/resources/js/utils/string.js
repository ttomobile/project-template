export function formatGreeting(name) {
  const target = typeof name === 'string' && name.trim().length > 0 ? name.trim() : 'there';
  return `Hello, ${target}!`;
}
