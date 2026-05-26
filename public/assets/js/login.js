import { ethers } from 'https://cdn.jsdelivr.net/npm/ethers@5.7.2/dist/ethers.esm.min.js';

const errorBox = document.getElementById('wallet-error');
const evmButtons = [
  document.getElementById('wallet-connect-button'),
  document.getElementById('evm-wallet-login'),
].filter(Boolean);
const solanaButton = document.getElementById('solana-wallet-login');

const setError = (message) => {
  if (!errorBox) return;
  if (!message) {
    errorBox.classList.add('hidden');
    errorBox.textContent = '';
    return;
  }
  errorBox.textContent = message;
  errorBox.classList.remove('hidden');
};

const requestWalletNonce = async (chain, address) => {
  const response = await fetch(`/auth/wallet/nonce?chain=${encodeURIComponent(chain)}&address=${encodeURIComponent(address)}`, {
    credentials: 'same-origin',
  });
  const data = await response.json();
  if (!response.ok) {
    throw new Error(data.error || 'Impossibile creare il nonce wallet.');
  }
  return data;
};

const submitWalletSignature = async ({ chain, address, signature, nonce }) => {
  const response = await fetch('/auth/wallet/verify', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'same-origin',
    body: JSON.stringify({ chain, address, signature, nonce }),
  });
  const data = await response.json();
  if (!response.ok || !data.success) {
    throw new Error(data.error || 'Autenticazione wallet fallita.');
  }
  return data;
};

const getInjectedEvmProvider = () => {
  const providers = [];
  if (window.rabby?.provider) providers.push(window.rabby.provider);
  if (window.ethereum?.providers) providers.push(...window.ethereum.providers);
  if (window.ethereum && !window.ethereum.providers) providers.push(window.ethereum);
  if (window.phantom?.ethereum) providers.push(window.phantom.ethereum);
  return providers.find((provider) => provider?.isRabby)
    || providers.find((provider) => provider?.isMetaMask)
    || providers[0]
    || null;
};

const loginEvm = async (button) => {
  setError('');
  button.disabled = true;
  try {
    const injected = getInjectedEvmProvider();
    if (!injected) {
      throw new Error('Installa MetaMask, Rabby o un wallet EVM compatibile.');
    }

    await injected.request?.({ method: 'eth_requestAccounts' });
    const provider = new ethers.providers.Web3Provider(injected);
    const signer = provider.getSigner();
    const address = (await signer.getAddress()).toLowerCase();
    const { nonce, message } = await requestWalletNonce('evm', address);
    const signature = await signer.signMessage(message);
    const result = await submitWalletSignature({ chain: 'evm', address, signature, nonce });
    window.location.href = result.redirect || '/area-clienti';
  } catch (error) {
    setError(error?.message || 'Login EVM non riuscito.');
  } finally {
    button.disabled = false;
  }
};

const loginSolana = async () => {
  setError('');
  solanaButton.disabled = true;
  try {
    const provider = window.solana || window.phantom?.solana;
    if (!provider?.isPhantom && !provider?.connect) {
      throw new Error('Installa Phantom o un wallet Solana compatibile.');
    }

    const response = await provider.connect();
    const publicKey = response.publicKey || provider.publicKey;
    const address = publicKey?.toString();
    if (!address) {
      throw new Error('Address Solana non disponibile.');
    }

    const { nonce, message } = await requestWalletNonce('solana', address);
    const encoded = new TextEncoder().encode(message);
    const signed = await provider.signMessage(encoded, 'utf8');
    const signatureBytes = signed.signature || signed;
    const signature = bs58Encode(signatureBytes);
    const result = await submitWalletSignature({ chain: 'solana', address, signature, nonce });
    window.location.href = result.redirect || '/area-clienti';
  } catch (error) {
    setError(error?.message || 'Login Solana non riuscito.');
  } finally {
    solanaButton.disabled = false;
  }
};

const alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
const bs58Encode = (buffer) => {
  const bytes = [...new Uint8Array(buffer)];
  let digits = [0];
  for (const byte of bytes) {
    let carry = byte;
    for (let i = 0; i < digits.length; i++) {
      carry += digits[i] << 8;
      digits[i] = carry % 58;
      carry = Math.floor(carry / 58);
    }
    while (carry > 0) {
      digits.push(carry % 58);
      carry = Math.floor(carry / 58);
    }
  }
  for (const byte of bytes) {
    if (byte === 0) digits.push(0);
    else break;
  }
  return digits.reverse().map((digit) => alphabet[digit]).join('');
};

evmButtons.forEach((button) => button.addEventListener('click', () => loginEvm(button)));
if (solanaButton) {
  solanaButton.addEventListener('click', loginSolana);
}
