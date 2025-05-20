-- CREAMOS LA BASE DE DATOS Nessun_DormaDB  
DROP DATABASE IF EXISTS Nessun_DormaDB;
CREATE DATABASE Nessun_DormaDB;
USE Nessun_DormaDB;

-- CREAMOS LA TABLA DE USUARIOS
CREATE TABLE Users (
    userId INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL,
    passw0rd VARCHAR(255) NOT NULL,
    rol ENUM('Manager', 'Client') NOT NULL,
    managerId INT,
    client1Id INT,
    client2Id INT,
    client3Id INT,
    firstName VARCHAR(20) NOT NULL,
    lastName1 VARCHAR(20) NOT NULL,
    lastName2 VARCHAR(20),
    birthDate DATE NOT NULL,
    country VARCHAR(20) NOT NULL,
    nif VARCHAR(20) NOT NULL
);

-- CREAMOS LA TABLA DE IPs
CREATE TABLE ipTable (
    ipTableId INT PRIMARY KEY AUTO_INCREMENT,
    userId INT NOT NULL,
    ip VARCHAR(15) NOT NULL,
    verified INT DEFAULT 0,
    FOREIGN KEY (userId) REFERENCES Users(userId)
);

-- CREAMOS LA TABLA DE PORTFOLIOS
CREATE TABLE Portfolios (
    portfolioId INT PRIMARY KEY AUTO_INCREMENT,
    userId INT NOT NULL,
    FOREIGN KEY (userId) REFERENCES Users(userId)
);

-- CREAMOS LA TABLA DE CARTERAS
CREATE TABLE Wallets (
    walletId INT PRIMARY KEY AUTO_INCREMENT,
    portfolioId INT NOT NULL,
    walletAddress VARCHAR(255) NOT NULL,
    walletName VARCHAR(20) NOT NULL,
    walletType ENUM('Gold','Fiat','Crypto') NOT NULL,
    balance DECIMAL(10,2) NOT NULL,
    currency VARCHAR(20) NOT NULL,
    FOREIGN KEY (portfolioId) REFERENCES Portfolios(portfolioId)
);

-- CREAMOS LA TABLA DE TRANSACCIONES
CREATE TABLE Transactions (
    transactionId INT PRIMARY KEY AUTO_INCREMENT,
    walletId INT NOT NULL,
    transactionType ENUM('Deposit', 'Withdrawal', 'Trade', 'Transfer') NOT NULL,
    transactionDate DATETIME NOT NULL,
    transactionAmount DECIMAL(10,2) NOT NULL,
    transactionCurrency VARCHAR(20) NOT NULL,
    transactionStatus ENUM('Pending', 'Completed', 'Failed') NOT NULL,
    transactionDescription VARCHAR(255),
    transactionFee DECIMAL(10,2),
    transactionFeeCurrency VARCHAR(20),
    transactionHash VARCHAR(255),
    transactionFrom VARCHAR(255),
    transactionTo VARCHAR(255),
    FOREIGN KEY (walletId) REFERENCES Wallets(walletId)
);