# Personal Fitness Tracker — Flowcharts

> Four role-specific flowcharts. Each one is independent and easy to follow.

---

## 🌐 1. Unregistered / Public User

```mermaid
flowchart TD
    A([Visitor]) --> B[Home Page]
    B --> C{Choose Action}
    C --> D[Browse Fitness Tips]
    C --> E[Browse Workout Routines]
    C --> F[Browse Nutritional Advice]
    C --> G[Register New Account]
    C --> H[Login]

    G --> G1[Enter Name, Email, Password]
    G1 --> G2{Valid?}
    G2 -->|No| G1
    G2 -->|Yes| G3[Account Created]
    G3 --> H

    H --> H1{Credentials OK?}
    H1 -->|No| H
    H1 -->|Admin| AD([Admin Dashboard])
    H1 -->|Trainer| TR([Trainer Dashboard])
    H1 -->|User| US([User Dashboard])
```

---

## 👤 2. Registered User

```mermaid
flowchart TD
    A([Login as User]) --> B[User Dashboard]

    B --> LOG[Log Daily Activity]
    LOG --> L1[🏋️ Log Workout - name + duration]
    LOG --> L2[🥗 Log Meal - name + calories]
    LOG --> L3[💧 Log Water - amount in ml]
    L1 & L2 & L3 --> DB1[(activity_logs table)]

    B --> STATS[View Progress Stats]
    STATS --> S1[Total Workouts Count]
    STATS --> S2[Total Calories Logged]
    STATS --> S3[Average Water Intake]

    B --> CHART[📊 View Progress Charts]
    CHART --> C1[Workouts Per Day - Bar Chart]
    CHART --> C2[Calories Per Day - Bar Chart]
    CHART --> C3[Water Per Day - Line Chart]

    B --> PUBLIC[Browse Public Content]
    PUBLIC --> P1[Read Tips]
    PUBLIC --> P2[Read Routines and Post Feedback]
    PUBLIC --> P3[Read Nutritional Advice]

    B --> TRAINER[View Trainer Interactions]
    TRAINER --> T1[Replies to my feedback]
    TRAINER --> T2[Personal suggestions from trainers]

    B --> OUT[Logout]
```

---

## 🏋️ 3. Trainer

```mermaid
flowchart TD
    A([Login as Trainer]) --> B[Trainer Dashboard]

    B --> C1[Manage Routines - Add / Edit / Delete]
    B --> C2[Manage Tips - Add / Edit / Delete]
    B --> C3[Manage Nutritional Advice - Add / Edit / Delete]

    B --> FB[View User Feedback on My Routines]
    FB --> FB1{Reply?}
    FB1 -->|Yes| FB2[Post Reply]
    FB2 --> FB3[Reply visible on Routines page with Trainer badge]

    B --> USR[View Active Users List]
    USR --> USR1[Click View on a User]
    USR1 --> USR2[See their full Activity Log]
    USR2 --> USR3[Send Personal Suggestion]
    USR3 --> USR4[User sees it on their dashboard]

    B --> OUT[Logout]
```

---

## 🔑 4. Admin

```mermaid
flowchart TD
    A([Login as Admin]) --> B[Admin Dashboard]

    B --> STAT[System Stats Overview]
    STAT --> S1[Users count]
    STAT --> S2[Trainers count]
    STAT --> S3[Routines count]
    STAT --> S4[Total Activity Logs]

    B --> MU[Manage Users page]
    MU --> MU1[View all users - max 10 with Show More]
    MU1 --> MU2{Action}
    MU2 -->|Change Role| MU3[Update: User / Trainer / Admin]
    MU2 -->|Delete| MU4[Remove user from system]

    B --> CT[Create Trainer Account]
    CT --> CT1[Fill name, email, password]
    CT1 --> CT2[Trainer account created]

    B --> MC[Manage Content]
    MC --> MC1[Add / Edit / Delete Tips]
    MC --> MC2[Add / Edit / Delete Routines]
    MC --> MC3[Add / Edit / Delete Nutritional Advice]

    B --> MON[Monitor Activity]
    MON --> MON1[Recent User Logs - max 10 + Show More]
    MON --> MON2[Trainer Replies - max 10 + Show More]
    MON2 --> MON3[Delete any reply]

    B --> OUT[Logout]
```
