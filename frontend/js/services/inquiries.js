/*
 * Inquiry service – the only place the contact form talks to the backend.
 *
 * There is no backend yet, so submitInquiry() only SIMULATES a request:
 * it waits a moment and resolves. Nothing is sent or stored anywhere.
 *
 * Later phase (FlightPHP REST API): replace the simulated part with the
 * fetch call below. The contact page does not need to change.
 */

// Demo settings. Set `fail` to true to see how the page handles an error.
export const demoSettings = { delayMs: 800, fail: false };

/**
 * @param {{ name: string, email: string, subject: string, message: string }} inquiry
 * @returns {Promise<object>} the saved inquiry (from the API later)
 */
export async function submitInquiry(inquiry) {
  // --- Later phase: real request ---------------------------------------
  // const response = await fetch(`${API_BASE_URL}/inquiries`, {
  //   method: 'POST',
  //   headers: { 'Content-Type': 'application/json' },
  //   body: JSON.stringify(inquiry),
  // });
  // if (!response.ok) throw new Error(`Request failed (HTTP ${response.status})`);
  // return response.json();
  // ----------------------------------------------------------------------

  await new Promise((resolve) => setTimeout(resolve, demoSettings.delayMs));
  if (demoSettings.fail) throw new Error('Simulated network error');
  return { ...inquiry, simulated: true };
}
