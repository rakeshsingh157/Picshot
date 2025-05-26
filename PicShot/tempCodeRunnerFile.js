const options = {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6IjYwNDgxMmI4LWU1NTUtNGQ3Yy1hYmYwLWZlYWVhOGVlMWNiZCIsInVzZXJfaWQiOiI2MDQ4MTJiOC1lNTU1LTRkN2MtYWJmMC1mZWFlYThlZTFjYmQiLCJhdWQiOiJhY2Nlc3MiLCJleHAiOjAuMH0.aUewSGRTSQRy_VzWHucXpILd7c5BDATMipUMCI8CBxc',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    image_url: 'https://i.ibb.co/YOUR_IMAGE_PATH.jpg'
  })
};

fetch('https://api.aiornot.com/v1/reports/image-url', options)
  .then(response => response.json())
  .then(data => console.log(data))
  .catch(error => console.error('Error:', error));