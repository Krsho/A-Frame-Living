/*
 * EXAMPLE DATA for the static Milestone 1 preview of the Profile and Admin pages.
 * Written for the university assignment – these people and messages are not real.
 * In the backend phase this file is removed and the data comes from the REST API
 * (tables `users` and `inquiries`, see docs/database-plan.md).
 */

export const demoUsers = [
  { id: 1, firstName: 'Admin', lastName: 'User', email: 'admin@aframe-living.example', role: 'admin', createdAt: '2026-09-01' },
  { id: 2, firstName: 'Ana', lastName: 'Kovač', email: 'ana.kovac@example.com', phone: '+387 61 000 001', role: 'customer', createdAt: '2026-10-02' },
  { id: 3, firstName: 'Emir', lastName: 'Hadžić', email: 'emir.hadzic@example.com', phone: null, role: 'customer', createdAt: '2026-10-11' },
  { id: 4, firstName: 'Lena', lastName: 'Marić', email: 'lena.maric@example.com', phone: '+387 62 000 002', role: 'customer', createdAt: '2026-10-19' },
];

// `userId` is null for inquiries sent by guests; `model` is a slug from houses.js or null.
export const demoInquiries = [
  { id: 1, userId: 2, name: 'Ana Kovač', email: 'ana.kovac@example.com', model: 'norway', subject: 'Norway on Kupres', status: 'answered', createdAt: '2026-10-03' },
  { id: 2, userId: 2, name: 'Ana Kovač', email: 'ana.kovac@example.com', model: 'norway', subject: 'Turnkey timeline question', status: 'in_progress', createdAt: '2026-10-20' },
  { id: 3, userId: 3, name: 'Emir Hadžić', email: 'emir.hadzic@example.com', model: 'continental', subject: 'Inquiry about the Continental model', status: 'new', createdAt: '2026-10-22' },
  { id: 4, userId: null, name: 'Marko Petrović', email: 'marko.p@example.com', model: null, subject: 'Do you build near Mostar?', status: 'new', createdAt: '2026-10-24' },
  { id: 5, userId: 4, name: 'Lena Marić', email: 'lena.maric@example.com', model: 'atlantic', subject: 'Atlantic as a weekend house', status: 'closed', createdAt: '2026-10-05' },
];

// The customer shown on the Profile preview.
export const demoCurrentUserId = 2;
