<script setup>
import { Head } from '@inertiajs/vue3';
import LandingNav from '@/components/landing/LandingNav.vue';
import HeroSection from '@/components/landing/HeroSection.vue';
import ProblemSection from '@/components/landing/ProblemSection.vue';
import SolutionSection from '@/components/landing/SolutionSection.vue';
import FeaturesSection from '@/components/landing/FeaturesSection.vue';
import RolesSection from '@/components/landing/RolesSection.vue';
import AiEngineSection from '@/components/landing/AiEngineSection.vue';
import WorkflowSection from '@/components/landing/WorkflowSection.vue';
import WhySection from '@/components/landing/WhySection.vue';
import StatsSection from '@/components/landing/StatsSection.vue';
import TechStackSection from '@/components/landing/TechStackSection.vue';
import CreatorSection from '@/components/landing/CreatorSection.vue';
import FaqSection from '@/components/landing/FaqSection.vue';
import CtaSection from '@/components/landing/CtaSection.vue';
import LandingFooter from '@/components/landing/LandingFooter.vue';
</script>

<template>
    <Head title="UniGPT — AI Academic Copilot for Universities">
        <meta
            name="description"
            content="UniGPT is an AI academic copilot for universities. Grounded, cited answers from your own documents, automated teaching tools and live operational insight — for students, faculty and administrators."
        />
    </Head>

    <div class="min-h-dvh bg-bg text-content antialiased">
        <LandingNav />

        <main>
            <HeroSection />
            <ProblemSection />
            <SolutionSection />
            <FeaturesSection />
            <RolesSection />
            <AiEngineSection />
            <WorkflowSection />
            <WhySection />
            <StatsSection />
            <TechStackSection />
            <CreatorSection />
            <FaqSection />
            <CtaSection />
        </main>

        <LandingFooter />
    </div>
</template>
