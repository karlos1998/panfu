<script setup lang="ts">
import PandaAvatar from '@/Components/Panfu/PandaAvatar.vue';
import PanelCard from '@/Components/PanelCard.vue';
import PanfuLayout from '@/Layouts/PanfuLayout.vue';
import type { MetaContent, TeamGroupData, TeamInfoCardData } from '@/types/panfu';

defineProps<{
    meta: MetaContent;
    groups: TeamGroupData[];
    about: TeamInfoCardData;
    joining: TeamInfoCardData;
}>();

const groupIcons: Record<TeamGroupData['key'], string> = {
    administrators: 'tools',
    moderators: 'moderator',
    sheriffs: 'star',
};
</script>

<template>
    <PanfuLayout
        :meta="meta"
        logo="/vendor/panfu-me/assets/panfu-logo-BkIF66dU.svg"
        main-class="panfu-main--team"
    >
        <section class="panfu-team">
            <div class="panfu-team__grid">
                <div class="panfu-team__groups">
                    <PanelCard
                        v-for="group in groups"
                        :key="group.key"
                        class="panfu-team-card"
                        severity="brand"
                    >
                        <template #title>
                            <span
                                :class="['panfu-fa', `panfu-fa--${groupIcons[group.key]}`]"
                                aria-hidden="true"
                            />
                            {{ group.title }}
                        </template>
                        <template #actions>
                            <span class="panfu-team-card__description">{{ group.description }}</span>
                        </template>

                        <div v-if="group.members.length" class="panfu-team-members">
                            <article
                                v-for="member in group.members"
                                :key="member.id"
                                class="panfu-team-member"
                                :class="{ 'panfu-team-member--offline': !member.online }"
                            >
                                <PandaAvatar :avatar="member.avatar" :name="member.name" />
                                <div class="panfu-team-member__copy">
                                    <strong>
                                        <span
                                            class="panfu-team-member__status"
                                            :class="member.online ? 'panfu-team-member__status--online' : 'panfu-team-member__status--offline'"
                                            aria-hidden="true"
                                        />
                                        {{ member.name }}
                                    </strong>
                                    <span>{{ member.roleLabel }}</span>
                                </div>
                            </article>
                        </div>
                        <p v-else class="panfu-team-empty">{{ group.emptyMessage }}</p>
                    </PanelCard>
                </div>

                <aside class="panfu-team__sidebar">
                    <PanelCard class="panfu-team-card panfu-team-info" severity="brand">
                        <template #title>
                            <span class="panfu-fa panfu-fa--info" aria-hidden="true" />
                            {{ about.title }}
                        </template>
                        <p v-for="paragraph in about.paragraphs" :key="paragraph" v-html="paragraph" />
                    </PanelCard>

                    <PanelCard class="panfu-team-card panfu-team-info" severity="brand">
                        <template #title>
                            <span class="panfu-fa panfu-fa--question" aria-hidden="true" />
                            {{ joining.title }}
                        </template>
                        <img
                            class="panfu-team-info__star"
                            src="/vendor/panfu-me/assets/sheriff-star.svg"
                            alt=""
                        />
                        <p v-for="paragraph in joining.paragraphs" :key="paragraph">{{ paragraph }}</p>
                    </PanelCard>
                </aside>
            </div>
        </section>
    </PanfuLayout>
</template>
