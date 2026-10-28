---
title: "FAQ"
nav_order: 12
description: "Short answers about darvis/printserver: what it is, what it needs, why it pulls instead of pushes, and what runs on the box on location."
faq: true
---

# Frequently asked questions

{% for item in site.data.faq %}
## {{ item.q }}

{{ item.a | markdownify }}
{% endfor %}
