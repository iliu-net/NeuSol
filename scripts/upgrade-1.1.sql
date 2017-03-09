ALTER TABLE nsCategory ADD categoryTypeId INT NOT NULL DEFAULT 0;
UPDATE nsCategory SET categoryTypeId = 2 WHERE categoryId > 19;
UPDATE nsCategory SET categoryTypeId = 1 WHERE categoryId < 20;
UPDATE nsCategory SET categoryTypeId = 3 WHERE categoryId IN (23,24);
