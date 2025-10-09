<header class="space-y-1">
    <h4 class="text-xl font-semibold">Binance</h4>
    <p class="text-sm text-base-content/70">
        Complete Binance verification and create restricted API keys before adding them to M.A.E.V.E.
    </p>
</header>
<section class="space-y-2">
    <h5 class="font-semibold">Step 1: Verification</h5>
    <p>Before you create API keys, make sure your Binance account meets the following requirements:</p>
    <ul class="list-disc space-y-1 pl-6">
        <li>Verify your identity (<a class="link" href="https://www.youtube.com/watch?v=yvtGCZ8ENEQ" target="_blank" rel="noopener noreferrer">tutorial</a>)</li>
        <li>Enable two-factor authentication (2FA) (<a class="link" href="https://www.youtube.com/watch?v=JRn6c7l7XPs&amp;pp=0gcJCdgAo7VqN5tD" target="_blank" rel="noopener noreferrer">tutorial</a>)</li>
        <li>Deposit any amount into your Spot Wallet to activate the account (<a class="link" href="https://www.youtube.com/watch?v=KAuoySzS0Mc" target="_blank" rel="noopener noreferrer">tutorial</a>)</li>
    </ul>
</section>
<section class="space-y-2">
    <h5 class="font-semibold">Step 2: Generate keys</h5>
    <p>Follow these steps to create secure Binance API keys:</p>
    <ol class="list-decimal space-y-1 pl-6">
        <li>Open your profile menu and select <strong>Account</strong>.</li>
        <li>Navigate to <strong>API Management</strong> and click <strong>Create API</strong>.</li>
        <li>Select <strong>Enable Spot &amp; Margin Trading</strong> and <strong>Enable Reading</strong>.</li>
        <li>Enable <strong>Restrict Access to - Trusted IPs</strong> and add <code>164.132.224.171</code>.</li>
    </ol>
    <p class="text-sm text-base-content/70"><code>164.132.224.171</code> is the secure IP address that runs your M.A.E.V.E instance.</p>
    <p>Keep the following permissions <strong>disabled</strong> to protect your account:</p>
    <ul class="list-disc space-y-1 pl-6">
        <li>Futures</li>
        <li>Withdrawals</li>
        <li>Internal Transfer</li>
        <li>European Options</li>
        <li>Margin Loan, Repay &amp; Transfer</li>
        <li>Universal Transfer</li>
        <li>Symbol Whitelist Edit</li>
    </ul>
    <p>After creating and securing the keys, add them to the Settings form and save.</p>
</section>
