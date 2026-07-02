# 📊 DELTA

## Plateforme de gestion d'audit financier

![PHP Version](https://img.shields.io/badge/PHP-8.2-blue)
![MySQL](https://img.shields.io/badge/MySQL-8.0-orange)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple)
![License](https://img.shields.io/badge/License-MIT-green)
![Version](https://img.shields.io/badge/Version-1.0.0-brightgreen)

---

## 📋 Table des matières

1. [Description](#-description)
2. [Fonctionnalités](#-fonctionnalités)
3. [Prérequis](#-prérequis)
4. [Installation](#-installation)
5. [Configuration](#-configuration)
6. [Identifiants de connexion](#-identifiants-de-connexion)
7. [Structure du projet](#-structure-du-projet)
8. [Technologies utilisées](#-technologies-utilisées)
9. [Normes appliquées](#-normes-appliquées)
10. [Sécurité](#-sécurité)
11. [Dépannage](#-dépannage)
12. [Auteur](#-auteur)
13. [Licence](#-licence)

---

## 📋 Description

**DELTA** est une plateforme web complète de gestion d'audit financier, conforme aux normes **ISA** (International Standards on Auditing) et **OHADA** (Organisation pour l'Harmonisation en Afrique du Droit des Affaires).

### Problématique
> *"L'audit financier suit une méthodologie normalisée mais reste largement papier dans beaucoup de cabinets."*

### Solution
DELTA digitalise l'ensemble du processus d'audit : planification, exécution, revue et reporting.

---

## 🎯 Fonctionnalités

| Module | Description |
|--------|-------------|
| 🔐 **Authentification** | Connexion sécurisée avec rôles (Admin, Associé, Manager, Auditeur) |
| 📊 **Tableau de bord** | Statistiques, graphiques et activités récentes |
| 📋 **Missions** | CRUD complet avec les 3 seuils de matérialité (ISA 320 & 450) |
| 🏢 **Entités** | Gestion des clients/entités auditées |
| 📄 **Feuilles de travail** | Standardisées par cycle d'audit (Ventes, Achats, Paie, etc.) |
| ⚠️ **Observations** | Suivi des anomalies avec niveaux de sévérité |
| 📧 **Circularisation** | Envoi et suivi des confirmations externes (ISA 505) |
| 📄 **Rapports** | Types ISA 700/705 (Sans réserve, Avec réserve, Refus, Défavorable) |
| ⚙️ **Administration** | Gestion des utilisateurs et sauvegarde BDD |

---

## 🛠️ Installation

## 🛠️ Prérequis

Avant d'installer DELTA, assurez-vous d'avoir les éléments suivants :

### Logiciels requis

| Logiciel | Version | Lien de téléchargement |
|----------|---------|----------------------|
| **XAMPP** | 8.2+ | [https://www.apachefriends.org/](https://www.apachefriends.org/) |
| **Navigateur Web** | Moderne | Chrome, Firefox, Edge, Opera |
| **Git** | 2.x | [https://git-scm.com/](https://git-scm.com/) |
| **Éditeur de code** | - | VS Code, Notepad++, Sublime Text |

### Configuration minimale

| Composant | Configuration |
|-----------|---------------|
| **Système d'exploitation** | Windows 10/11, Linux, macOS |
| **Mémoire RAM** | 2 GB minimum |
| **Espace disque** | 100 MB minimum |
| **Processeur** | 1 GHz minimum |

### Extensions PHP requises

```ini
# Extensions activées dans php.ini
extension=mysqli
extension=pdo_mysql
extension=curl
extension=gd
extension=mbstring
extension=openssl
