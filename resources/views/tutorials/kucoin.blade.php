<header class="space-y-1">
    <h4 class="text-xl font-semibold">KuCoin</h4>
    <p class="text-sm text-base-content/70">KuCoin trades against USDT. Ensure you hold USDT before enabling M.A.E.V.E.</p>
</header>
<section class="space-y-2">
    <h5 class="font-semibold">Create secure credentials</h5>
    <ol class="list-decimal space-y-1 pl-6">
        <li>Go to <strong>Account Settings</strong> &rarr; <strong>API</strong>.</li>
        <li>Click <strong>Create API Key</strong>.</li>
        <li>Enable <strong>Spot Trading</strong> and <strong>Margin Trading</strong>.</li>
        <li>Select <strong>Restricted to Trusted IPs Only</strong> and add <code>164.132.224.171</code>.</li>
        <li>Store your <strong>API Passphrase</strong> securely alongside the API key and secret.</li>
        <li>Click <strong>Generate Key</strong> to finish.</li>
    </ol>
    <p class="text-sm text-base-content/70"><code>164.132.224.171</code> is the secure IP address that runs your M.A.E.V.E instance.</p>
    <p>Keep these permissions <strong>disabled</strong>:</p>
    <ul class="list-disc space-y-1 pl-6">
        <li>KuCoin Earn</li>
        <li>Futures Trading</li>
        <li>Withdrawal</li>
        <li>FlexTransfers</li>
    </ul>
    <p>Enter the API Key, Secret Key, and Passphrase in the Settings form when you are done.</p>
</section>
