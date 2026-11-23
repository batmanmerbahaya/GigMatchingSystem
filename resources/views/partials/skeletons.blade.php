{{--
    Skeleton screens shown while the next page loads (app.js). A link picks one with
    data-skeleton="jobs|list|table|detail|form". They copy the shape of the real pages.
--}}
@php
    $skCard = '<div class="gig gig-sk"><div class="gig-head"><span class="sk sk-tile"></span><div class="sk-stack"><span class="sk sk-title w-90"></span><span class="sk sk-title w-60"></span><span class="sk sk-sm w-45"></span></div></div><span class="sk sk-pill"></span><div class="sk-stack sk-meta"><span class="sk sk-line w-70"></span><span class="sk sk-line w-55"></span></div><div class="gig-foot"><span class="sk sk-sm w-35"></span><span class="sk sk-btn"></span></div></div>';
    $skAppCard = '<div class="app-card"><div class="app-top"><span class="sk sk-tile"></span><div class="sk-stack"><span class="sk sk-title w-60"></span><span class="sk sk-sm w-35"></span><span class="sk sk-pill"></span></div><span class="sk sk-badge"></span></div><span class="sk sk-bar w-100"></span><div class="app-bottom"><span class="sk sk-line w-55"></span><span class="sk sk-btn"></span></div></div>';
    $skRow = '<div class="h-row"><span class="sk sk-tile tile"></span><div class="h-main sk-stack"><span class="sk sk-line w-70"></span><span class="sk sk-sm w-45"></span></div><span class="sk sk-pill h-pay"></span><span class="sk sk-badge h-stage"></span><span class="sk sk-sm w-80 h-date"></span><span class="sk sk-btn h-actions"></span></div>';
    $skHead = '<div class="page-head"><span class="sk sk-title w-35"></span></div>';
@endphp

<template id="sk-jobs">
    {!! $skHead !!}
    <div class="search"><span class="sk sk-input w-100"></span><div class="sk-row"><span class="sk sk-tile"></span><span class="sk sk-tile"></span><span class="sk sk-tile"></span><span class="sk sk-tile"></span><span class="sk sk-tile"></span><span class="sk sk-tile"></span></div></div>
    <div class="gigs">{!! str_repeat($skCard, 4) !!}</div>
</template>

<template id="sk-cards">
    <div class="gigs">{!! str_repeat($skCard, 4) !!}</div>
</template>

<template id="sk-list">
    {!! $skHead !!}
    <div class="sum-row"><span class="sk sk-sum"></span><span class="sk sk-sum"></span><span class="sk sk-sum"></span></div>
    <div class="apps-list">{!! str_repeat($skAppCard, 3) !!}</div>
</template>

<template id="sk-table">
    {!! $skHead !!}
    <div class="sum-row"><span class="sk sk-sum"></span><span class="sk sk-sum"></span><span class="sk sk-sum"></span></div>
    <div class="panel h-panel"><div class="h-bar"><span class="sk sk-title w-35"></span></div>{!! str_repeat($skRow, 4) !!}</div>
</template>

<template id="sk-detail">
    <div class="detail-page">
        <div class="detail-hero"><span class="sk sk-tile-lg"></span><div class="sk-stack"><span class="sk sk-title w-70"></span><span class="sk sk-sm w-35"></span></div><span class="sk sk-badge"></span></div>
        <div class="detail-section"><div class="facts"><span class="sk sk-fact"></span><span class="sk sk-fact"></span><span class="sk sk-fact"></span><span class="sk sk-fact"></span></div></div>
        <div class="detail-section"><span class="sk sk-line w-35"></span><div class="sk-stack"><span class="sk sk-line w-100"></span><span class="sk sk-line w-90"></span><span class="sk sk-line w-55"></span></div></div>
    </div>
</template>

<template id="sk-pane">
    <div class="detail-hero"><span class="sk sk-tile-lg"></span><div class="sk-stack"><span class="sk sk-title w-70"></span><span class="sk sk-sm w-35"></span></div><span class="sk sk-badge"></span></div>
    <div class="detail-section"><div class="facts"><span class="sk sk-fact"></span><span class="sk sk-fact"></span><span class="sk sk-fact"></span><span class="sk sk-fact"></span></div></div>
    <div class="detail-section"><span class="sk sk-line w-35"></span><div class="sk-stack"><span class="sk sk-line w-100"></span><span class="sk sk-line w-90"></span><span class="sk sk-line w-90"></span><span class="sk sk-line w-55"></span></div></div>
    <div class="detail-section poster-body"><span class="sk sk-sm w-35"></span><div class="poster-row"><span class="sk sk-avatar"></span><div class="sk-stack"><span class="sk sk-line w-50"></span><span class="sk sk-sm w-35"></span></div></div></div>
</template>

<template id="sk-form">
    {!! $skHead !!}
    <div class="panel"><div class="panel-body sk-stack"><span class="sk sk-sm w-35"></span><span class="sk sk-input w-100"></span><span class="sk sk-sm w-35"></span><span class="sk sk-area w-100"></span><span class="sk sk-sm w-35"></span><span class="sk sk-input w-100"></span><span class="sk sk-btn-lg"></span></div></div>
</template>
